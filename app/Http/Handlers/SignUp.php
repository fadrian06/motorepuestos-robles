<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use Leaf\Db;
use Leaf\Flash;
use Leaf\Form;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class SignUp implements RequestHandlerInterface
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
        $authTable = (string) $this->auth->config('db.table');
        $passwordKey = (string) $this->auth->config('password.key');
        $redirectLogin = (string) $this->auth->config('redirect.login');
        $redirectGuest = (string) $this->auth->config('redirect.guest');
        $rolesKey = (string) $this->auth->config('roles.key');
        $db = $this->auth->db();

        /** @var array */
        $requestBody = $request->getParsedBody();

        $db->select($authTable);
        $usersCount = $db->count();

        if ($usersCount) {
            return $this->responseFactory->createResponse()->withHeader(
                'location',
                $redirectLogin,
            );
        }

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/signup.twig', [
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
                    $response = $this->responseFactory->createResponse(400);

                    return $response->withHeader('location', '/registrarse');
                }

                if ($this->auth->register([
                    ...$validated,
                    $rolesKey => json_encode(['admin']),
                    'permissions' => json_encode([]),
                ])) {
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader('location', $redirectGuest);
                }

                $this->flash->set($this->auth->errors());
                $response = $this->responseFactory->createResponse();

                return $response->withHeader('location', '/registrarse');

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
