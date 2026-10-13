<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use NoDiscard;
use Override;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class VerifyEmail implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        private Environment $environment,
        private PHPMailer $mail,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string */
        $redirectGuest = $this->auth->config('redirect.guest');

        /** @var string */
        $token = $request->getQueryParams()['token'] ?? '';

        if ($this->auth->user()?->isVerified()) {
            $response = $this->responseFactory->createResponse();

            return $response->withHeader('location', $redirectGuest);
        }

        switch ($request->getMethod()) {
            case 'GET':
                if ($this->auth->verifyToken($token)?->verifyEmail()) {
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader('location', $redirectGuest);
                }

                $html = $this->environment->render('pages/verify-email.twig');
                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                $token = $this->auth->user()?->generateVerificationToken();

                /** @var string */
                $email = $this->auth->user()?->get()['email'] ?? '';

                $mailBody = $this->environment->render('emails/verify-email-token.twig', [
                    'appUrl' => $_ENV['APP_URL'],
                    'token' => $token,
                ]);

                $this->mail->addAddress($email);
                $this->mail->Subject = 'Verifica tu correo electronico';
                $this->mail->Body = $mailBody;
                $this->mail->send();

                $response = $this->responseFactory->createResponse();

                return $response->withHeader('location', '/verificar-correo');

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
