<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use Leaf\Flash;
use Leaf\Form;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class SignIn implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Environment $environment,
        private Form $form,
        private Auth $auth,
        private Flash $flash,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $passwordKey = (string) $this->auth->config('password.key');
        $redirectLogin = (string) $this->auth->config('redirect.login');
        $redirectGuest = (string) $this->auth->config('redirect.guest');

        /** @var array */
        $requestBody = $request->getParsedBody();

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/signin.twig', [
                    'passwordKey' => $passwordKey,
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                /** @var false|array<string, string> */
                $validated = $this->form->validate($requestBody, [
                    'email' => 'email',
                    $passwordKey => 'string',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader('location', $redirectLogin);
                }

                if (!$this->auth->login($validated)) {
                    $this->flash->set($this->auth->errors());
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader('location', $redirectLogin);
                }

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    $redirectGuest,
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
