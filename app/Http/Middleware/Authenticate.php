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
        if ($this->auth->user()) {
            /** @var string */
            $email = $this->auth->user()?->email;
            $this->auth->user()?->assign($email);

            return $handler->handle($request);
        }

        $redirectLogin = (string) $this->auth->config('redirect.login');

        return $this->responseFactory->createResponse()->withHeader(
            'location',
            $redirectLogin,
        );
    }
}
