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

final readonly class Authenticate implements MiddlewareInterface
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
        $redirectLogin = (string) $this->auth->config('redirect.login');
        $user = $this->auth->user();

        if ($user) {
            $email = (string) $user->email;
            $this->auth->user()?->assign($email);

            return $handler->handle($request);
        }

        return $this->responseFactory->createResponse()->withHeader(
            'location',
            $redirectLogin,
        );
    }
}
