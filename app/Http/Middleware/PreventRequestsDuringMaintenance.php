<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

use function Faslatam\PsrFramework\getenv;

final readonly class PreventRequestsDuringMaintenance implements
    MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Environment $environment,
    ) {}

    #[Override]
    #[NoDiscard]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (getenv('APP_MAINTENANCE')) {
            $html = $this->environment->render('pages/maintenance.twig');

            $response = $this->responseFactory->createResponse(503);
            $response->getBody()->write($html);

            return $response;
        }

        return $handler->handle($request);
    }
}
