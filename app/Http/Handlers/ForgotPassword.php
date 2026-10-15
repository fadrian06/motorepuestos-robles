<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
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

final readonly class ForgotPassword implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Environment $environment,
        private Flash $flash,
        private Form $form,
        private Auth $auth,
        private PHPMailer $mail,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $requestBody = $request->getParsedBody();

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/forgot-password.twig', [
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                /** @var false|array<string, string> */
                $validated = $this->form->validate($requestBody, [
                    'email' => 'email',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader(
                        'location',
                        '/recuperar-clave',
                    );
                }

                $this->auth->config('password.key', false);

                if ($this->auth->login($validated)) {
                    $token = $this->auth->user()?->generateVerificationToken();

                    /** @var string */
                    $email = $this->auth->user()?->get()['email'] ?? '';

                    $mailBody = $this->environment->render('emails/password-reset-token.twig', [
                        'appUrl' => $_ENV['APP_URL'],
                        'token' => $token,
                    ]);

                    $this->mail->addAddress($email);
                    $this->mail->Subject = 'Recuperar clave';
                    $this->mail->Body = $mailBody;
                    $this->mail->send();
                    $this->auth->logout();

                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader(
                        'location',
                        '/recuperar-clave',
                    );
                }

                $this->flash->set($this->auth->errors());

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/recuperar-clave',
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
