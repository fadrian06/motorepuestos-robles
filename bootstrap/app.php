<?php

declare(strict_types=1);

use App\Http\Handlers\NotFound;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\RouterFactory;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\RoutingMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

return static function (ContainerInterface $container): RequestHandlerInterface {
    $middlewares = [PreventRequestsDuringMaintenance::class];

    $routesFiles = (array) glob(__DIR__ . '/../routes/*.php');

    foreach (array_filter($routesFiles) as $routesFile) {
        $routerFactory = (object) require $routesFile;

        if ($routerFactory instanceof RouterFactory) {
            $router = $routerFactory($container);
            $middlewares[] = new RoutingMiddleware($router);
        }
    }

    return new QueueRequestHandler(
        NotFound::class,
        ...$middlewares,
    )->setContainer($container);
};
