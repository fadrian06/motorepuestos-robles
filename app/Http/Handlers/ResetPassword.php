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

final readonly class ResetPassword implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        private Environment $environment,
        private Form $form,
        private Flash $flash,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string */
        $token = $request->getQueryParams()['token'] ?? '';

        $authTable = (string) $this->auth->config('db.table');
        $passwordKey = (string) $this->auth->config('password.key');
        $redirectGuest = (string) $this->auth->config('redirect.guest');

        /** @var callable(string): string */
        $hasher = $this->auth->config('password.encode');

        /** @var array */
        $requestBody = $request->getParsedBody();

        $user = $this->auth->verifyToken($token);

        if (!$user) {
            $this->flash->set([
                'El token de recuperación de contraseña es inválido o ha expirado.',
            ]);

            return $this->responseFactory->createResponse()->withHeader(
                'location',
                '/recuperar-clave',
            );
        }

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/reset-password.twig', [
                    'passwordKey' => $passwordKey,
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                /** @var false|array<string, string> */
                $validated = $this->form->validate($requestBody, [
                    $passwordKey => 'string',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader(
                        'location',
                        "/restablecer-clave?token={$token}",
                    );
                }

                /** @var string */
                $email = $user->email;

                $password = $hasher($validated[$passwordKey]);

                $this->auth
                    ->db()
                    ->update($authTable)
                    ->params([$passwordKey => $password])
                    ->where('email', $email)
                    ->execute();

                $this->auth->login([
                    'email' => $email,
                    $passwordKey => $validated[$passwordKey],
                ]);

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    $redirectGuest,
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
