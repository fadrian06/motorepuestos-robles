<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use Leaf\Db;
use Leaf\Flash;
use Leaf\Form;
use NoDiscard;
use Override;
use PHPMailer\PHPMailer\PHPMailer;
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
        private PHPMailer $mail,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string */
        $authTable = $this->auth->config('db.table');

        /** @var string */
        $passwordKey = $this->auth->config('password.key');

        /** @var string */
        $redirectLogin = $this->auth->config('redirect.login');

        /** @var string */
        $redirectGuest = $this->auth->config('redirect.guest');

        /** @var string */
        $rolesKey = $this->auth->config('roles.key');

        /** @var array */
        $requestBody = $request->getParsedBody();

        /** @var Db */
        $db = $this->auth->db()->select($authTable);
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

                if ($flash) {
                    dd($flash);
                }

                $html = $this->environment->render('pages/signup.twig', [
                    'passwordKey' => $passwordKey,
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
                    $token = $this->auth->user()?->generateVerificationToken();

                    $mailBody = $this->environment->render('verify-email-token.twig', [
                        'appUrl' => $_ENV['APP_URL'],
                        'token' => $token,
                    ]);

                    $this->mail->addAddress($validated['email']);
                    $this->mail->Subject = 'Verifica tu correo electronico';
                    $this->mail->Body = $mailBody;
                    $this->mail->send();

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
