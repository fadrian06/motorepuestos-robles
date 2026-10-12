<?php

declare(strict_types=1);

use App\Http\Handlers\Dashboard;
use App\Http\Handlers\DeleteClient;
use App\Http\Handlers\ForgotPassword;
use App\Http\Handlers\ListClients;
use App\Http\Handlers\ListSellers;
use App\Http\Handlers\NotFound;
use App\Http\Handlers\RegisterClient;
use App\Http\Handlers\RegisterSeller;
use App\Http\Handlers\ResetPassword;
use App\Http\Handlers\ResetPermissions;
use App\Http\Handlers\SignIn;
use App\Http\Handlers\SignOut;
use App\Http\Handlers\SignUp;
use App\Http\Handlers\UpdateClient;
use App\Http\Handlers\UpdatePassword;
use App\Http\Handlers\VerifyEmail;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\RedirectIfAuthenticated;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\Router;
use Faslatam\PsrFramework\RoutingMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Container\Container;
use Leaf\Auth;
use Leaf\Helpers\Password;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

use function Faslatam\PsrFramework\send_response;

require_once __DIR__ . '/vendor/autoload.php';

$_ENV = file_exists(__DIR__ . '/.env')
    ? parse_ini_file(__DIR__ . '/.env')
    : parse_ini_file(__DIR__ . '/.env.example');

$container = Container::getInstance();
$container->singleton(ResponseFactoryInterface::class, HttpFactory::class);

$container->singleton(
    ServerRequestInterface::class,
    ServerRequest::fromGlobals(...),
);

$container->singleton(
    LoaderInterface::class,
    static fn(): LoaderInterface => new FilesystemLoader([
        __DIR__ . '/resources/views',
    ]),
);

$container->singleton(
    Environment::class,
    static function (Container $container): Environment {
        $environment = new Environment(
            $container->get(LoaderInterface::class),
        );

        $environment->addGlobal('_ENV', $_ENV);

        return $environment;
    },
);

$container->singleton(Auth::class, static function (): Auth {
    $auth = new Auth();

    $auth->config('timestamps', false);

    $auth->config(
        'password.encode',
        static fn(
            #[SensitiveParameter]
            string $password,
        ): string => (string) Password::hash($password, Password::BCRYPT, [
            'cost' => $_ENV['BCRYPT_ROUNDS'],
        ]),
    );

    $auth->config('session', true);
    $auth->config('token.secret', $_ENV['TOKEN_SECRET']);

    $auth->autoConnect();

    /** @var string */
    $authTable = $auth->config('db.table');

    /** @var string */
    $authIdKey = $auth->config('id.key');

    /** @var string */
    $passwordKey = $auth->config('password.key');

    $db = $auth->db();

    $db->createTableIfNotExists($authTable, [
        $authIdKey => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'email' => 'TEXT NOT NULL UNIQUE CHECK (email LIKE "%@%")',
        $passwordKey => 'TEXT NOT NULL',
        'permissions' => 'TEXT NOT NULL',
    ]);

    $db->execute();

    $db->createTableIfNotExists('clients', [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'name' => 'TEXT NOT NULL UNIQUE',
    ]);

    $db->execute();

    $db->select($authTable);

    /** @var array[] */
    $users = $db->all();
    $customPermissions = [];

    foreach ($users as $user) {
        /** @var string */
        $permissions = $user['permissions'];

        $customPermissions[$user['email']] = json_decode(
            $permissions,
            associative: true,
        );
    }

    /** @var array<string, string[]> */
    $roles = [
        'admin' => [
            'view sellers',
            'register seller',
            'reset seller permissions',
            'view clients',
            'register client',
            'update client',
            'delete client',
        ],
        'seller' => [],
        ...$customPermissions,
    ];

    $auth->createRoles($roles);

    return $auth;
});

$container->bind(PHPMailer::class, static function (): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) $_ENV['MAIL_PORT'];
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->isHTML(true);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    return $mail;
});

$fallbackHandler = $container->get(NotFound::class);
$authenticate = $container->get(Authenticate::class);
$redirectIfAuthenticated = $container->get(RedirectIfAuthenticated::class);
$ensureEmailIsVerified = $container->get(EnsureEmailIsVerified::class);
$authorize = $container->get(Authorize::class);
$dashboard = $container->get(Dashboard::class);
$signIn = $container->get(SignIn::class);
$signUp = $container->get(SignUp::class);
$signOut = $container->get(SignOut::class);
$verifyEmail = $container->get(VerifyEmail::class);
$forgotPassword = $container->get(ForgotPassword::class);
$resetPassword = $container->get(ResetPassword::class);
$updatePassword = $container->get(UpdatePassword::class);
$registerSeller = $container->get(RegisterSeller::class);
$listSellers = $container->get(ListSellers::class);
$resetPermissions = $container->get(ResetPermissions::class);
$registerClient = $container->get(RegisterClient::class);
$listClients = $container->get(ListClients::class);
$updateClient = $container->get(UpdateClient::class);
$deleteClient = $container->get(DeleteClient::class);
$auth = $container->get(Auth::class);
$redirectLogin = $auth->config('redirect.login');
$redirectGuest = $auth->config('redirect.guest');

$router = new Router();

$router->attach(
    ['GET'],
    ['#^/$#', "#^{$redirectGuest}$#"],
    new QueueRequestHandler($dashboard, $authenticate, $ensureEmailIsVerified),
);

$router->attach(
    ['GET', 'POST'],
    ["#^{$redirectLogin}$#"],
    new QueueRequestHandler($signIn, $redirectIfAuthenticated),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/registrarse$#'],
    new QueueRequestHandler($signUp, $redirectIfAuthenticated),
);

$router->attach(
    ['GET'],
    ['#^/salir$#'],
    new QueueRequestHandler($signOut, $authenticate),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/verificar-correo$#'],
    new QueueRequestHandler($verifyEmail, $authenticate),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/recuperar-clave$#'],
    new QueueRequestHandler($forgotPassword, $redirectIfAuthenticated),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/restablecer-clave$#'],
    new QueueRequestHandler($resetPassword, $redirectIfAuthenticated),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/actualizar-clave$#'],
    new QueueRequestHandler(
        $updatePassword,
        $authenticate,
        $ensureEmailIsVerified,
    ),
);

$router->attach(
    ['GET'],
    ['#^/vendedores$#'],
    new QueueRequestHandler(
        $listSellers,
        $authenticate,
        $ensureEmailIsVerified,
        $authorize->withPermissions('view sellers'),
    ),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/vendedores/registrar$#'],
    new QueueRequestHandler(
        $registerSeller,
        $authenticate,
        $ensureEmailIsVerified,
        $authorize->withPermissions('register seller'),
    ),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/vendedores/(?<id>\d+)$#'],
    new QueueRequestHandler(
        $resetPermissions,
        $authenticate,
        $ensureEmailIsVerified,
        $authorize->withPermissions('reset seller permissions'),
    ),
);

$router->attach(
    ['GET'],
    ['#^/clientes$#'],
    new QueueRequestHandler(
        $listClients,
        $authenticate,
        $ensureEmailIsVerified,
        $authorize->withPermissions('view clients'),
    ),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/clientes/registrar$#'],
    new QueueRequestHandler(
        $registerClient,
        $authenticate,
        $ensureEmailIsVerified,
        $authorize->withPermissions('register client'),
    ),
);

$router->attach(
    ['GET', 'POST'],
    ['#^/clientes/(?<id>\d+)$#'],
    new QueueRequestHandler(
        $updateClient,
        $authenticate,
        $ensureEmailIsVerified,
        $authorize->withPermissions('update client'),
    ),
);

$router->attach(
    ['GET'],
    ['#^/clientes/(?<id>\d+)/eliminar$#'],
    new QueueRequestHandler(
        $deleteClient,
        $authenticate,
        $ensureEmailIsVerified,
        $authorize->withPermissions('delete client'),
    ),
);

$routingMiddleware = new RoutingMiddleware($router);

$queueRequestHandler = new QueueRequestHandler(
    $fallbackHandler,
    $routingMiddleware,
);

$request = $container->get(ServerRequestInterface::class);
$response = $queueRequestHandler->handle($request);

send_response($response);
