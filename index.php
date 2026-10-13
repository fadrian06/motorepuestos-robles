<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Handlers\NotFound;
use App\Migration;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\Router;
use Faslatam\PsrFramework\RoutingMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Container\Container;
use Leaf\Auth;
use Leaf\Helpers\Password;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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

$container->singleton(Environment::class, static function (): Environment {
    $environment = new Environment(new FilesystemLoader([
        __DIR__ . '/resources/views',
    ]));

    $environment->addGlobal('_ENV', $_ENV);

    return $environment;
});

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

    $migrationFiles = array_filter((array) glob(__DIR__
    . '/database/migrations/*.php'));

    foreach ($migrationFiles as $migration) {
        /** @var Migration */
        $migration = require_once $migration;
        $migration->up($auth);
    }

    /** @var string */
    $authTable = $auth->config('db.table');
    $db = $auth->db();
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
        'admin' => Permission::keys(),
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
/** @var callable(ContainerInterface): Router */
$webRouterFactory = require_once __DIR__ . '/routes/web.php';
$router = $webRouterFactory($container);
$routingMiddleware = new RoutingMiddleware($router);

$queueRequestHandler = new QueueRequestHandler(
    $fallbackHandler,
    $routingMiddleware,
);

$request = $container->get(ServerRequestInterface::class);
$response = $queueRequestHandler->handle($request);

send_response($response);
