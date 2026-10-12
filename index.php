<?php

declare(strict_types=1);

use App\Http\Handlers\Dashboard;
use App\Http\Handlers\ForgotPassword;
use App\Http\Handlers\NotFound;
use App\Http\Handlers\ResetPassword;
use App\Http\Handlers\SignIn;
use App\Http\Handlers\SignOut;
use App\Http\Handlers\SignUp;
use App\Http\Handlers\VerifyEmail;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\RedirectIfAuthenticated;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\Router;
use Faslatam\PsrFramework\RoutingMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Container\Container;
use Leaf\Auth;
use Leaf\Db;
use Leaf\Helpers\Password;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
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

    /** @var Db */
    $db = $auth->db()->createTableIfNotExists($authTable, [
        $authIdKey => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'email' => 'TEXT NOT NULL UNIQUE CHECK (email LIKE "%@%")',
        $passwordKey => 'TEXT NOT NULL',
    ]);

    $db->execute();

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
$dashboard = $container->get(Dashboard::class);
$signIn = $container->get(SignIn::class);
$signUp = $container->get(SignUp::class);
$signOut = $container->get(SignOut::class);
$verifyEmail = $container->get(VerifyEmail::class);
$forgotPassword = $container->get(ForgotPassword::class);
$resetPassword = $container->get(ResetPassword::class);
$auth = $container->get(Auth::class);
$redirectLogin = $auth->config('redirect.login');
$redirectGuest = $auth->config('redirect.guest');

$router = new Router();

$router->attach(
    ['GET'],
    ["#^{$redirectGuest}$#"],
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

$routingMiddleware = new RoutingMiddleware($router);

$queueRequestHandler = new QueueRequestHandler(
    $fallbackHandler,
    $routingMiddleware,
);

$request = $container->get(ServerRequestInterface::class);
$response = $queueRequestHandler->handle($request);

send_response($response);
