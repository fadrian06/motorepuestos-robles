<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class NotFound implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Environment $environment,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $html = $this->environment->render('pages/404.twig');

        $response = $this->responseFactory->createResponse(404);
        $response->getBody()->write($html);

        return $response;
    }
}
