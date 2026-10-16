<?php

declare(strict_types=1);

use App\Http\Handlers\NotFound;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\Router;
use Faslatam\PsrFramework\RoutingMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

return static function (ContainerInterface $container): RequestHandlerInterface {
    /** @var RequestHandlerInterface */
    $fallbackHandler = $container->get(NotFound::class);
    /** @var callable(ContainerInterface): Router */
    $webRouterFactory = require_once __DIR__ . '/../routes/web.php';
    $router = $webRouterFactory($container);
    $routingMiddleware = new RoutingMiddleware($router);

    return new QueueRequestHandler($fallbackHandler, $routingMiddleware);
};
