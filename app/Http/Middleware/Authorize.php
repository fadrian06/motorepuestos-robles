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

final readonly class Authorize implements MiddlewareInterface
{
    /** @var string[] */
    private array $permissions;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        string ...$permissions,
    ) {
        $this->permissions = $permissions;
    }

    #[Override]
    #[NoDiscard]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var string */
        $redirectGuest = $this->auth->config('redirect.guest');

        if ($this->auth->user()?->can($this->permissions ?? [])) {
            return $handler->handle($request);
        }

        return $this->responseFactory->createResponse()->withHeader(
            'location',
            $redirectGuest,
        );
    }

    public function withPermissions(string ...$permissions): self
    {
        return new self($this->responseFactory, $this->auth, ...$permissions);
    }
}
