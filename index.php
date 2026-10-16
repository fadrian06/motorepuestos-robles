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

/** @var Closure(): Auth */
$authFactory = require_once __DIR__ . '/config/auth.php';

/** @var Closure(): PHPMailer */
$mailFactory = require_once __DIR__ . '/config/mail.php';

/** @var Closure(): Environment */
$viewFactory = require_once __DIR__ . '/config/view.php';

$container->singleton(Auth::class, $authFactory);
$container->bind(PHPMailer::class, $mailFactory);
$container->singleton(Environment::class, $viewFactory);

/** @var callable(ContainerInterface): RequestHandlerInterface */
$requestHandlerFactory = require_once __DIR__ . '/bootstrap/app.php';
$requestHandler = $requestHandlerFactory($container);

$request = $container->get(ServerRequestInterface::class);
$response = $requestHandler->handle($request);

send_response($response);
