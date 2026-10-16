<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Leaf\Auth;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
    ) {}

    #[Override]
    #[NoDiscard]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($this->auth->user()) {
            $redirectGuest = (string) $this->auth->config('redirect.guest');

            return $this->responseFactory->createResponse()->withHeader(
                'location',
                $redirectGuest,
            );
        }

        return $handler->handle($request);
    }
}
