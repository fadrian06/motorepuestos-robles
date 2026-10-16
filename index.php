<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Container\Container;
use Leaf\Auth;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

/** @var Closure(): Auth */
$authFactory = require_once __DIR__ . '/config/auth.php';

$container->singleton(Auth::class, $authFactory);

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

/** @var callable(ContainerInterface): RequestHandlerInterface */
$requestHandlerFactory = require_once __DIR__ . '/bootstrap/app.php';
$requestHandler = $requestHandlerFactory($container);

$request = $container->get(ServerRequestInterface::class);
$response = $requestHandler->handle($request);

send_response($response);
