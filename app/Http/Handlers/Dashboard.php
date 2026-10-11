<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class Dashboard implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Environment $environment,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $html = $this->environment->render('pages/dashboard.twig');
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
