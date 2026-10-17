<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
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
    /** @var Permission[] */
    private array $permissions;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        Permission ...$permissions,
    ) {
        $this->permissions = $permissions;
    }

    #[Override]
    #[NoDiscard]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $redirectGuest = (string) $this->auth->config('redirect.guest');

        $permissions = array_map(
            static fn(Permission $permission) => $permission->name,
            $this->permissions,
        );

        if ($this->auth->user()?->can($permissions)) {
            return $handler->handle($request);
        }

        return $this->responseFactory->createResponse()->withHeader(
            'location',
            $redirectGuest,
        );
    }

    public function withPermissions(Permission ...$permissions): self
    {
        return new self($this->responseFactory, $this->auth, ...$permissions);
    }
}
