<?php

declare(strict_types=1);

use App\Http\Handlers\NotFound;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\Router;
use Faslatam\PsrFramework\RoutingMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Container\Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

use function Faslatam\PsrFramework\send_response;

require_once __DIR__ . '/vendor/autoload.php';

$container = Container::getInstance();
$container->singleton(ResponseFactoryInterface::class, HttpFactory::class);

$container->singleton(
    ServerRequestInterface::class,
    ServerRequest::fromGlobals(...),
);

$fallbackHandler = $container->get(NotFound::class);
$router = new Router();
$routingMiddleware = new RoutingMiddleware($router);

$queueRequestHandler = new QueueRequestHandler(
    $fallbackHandler,
    $routingMiddleware,
);

$request = $container->get(ServerRequestInterface::class);
$response = $queueRequestHandler->handle($request);

send_response($response);
