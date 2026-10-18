<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use Leaf\Flash;
use NoDiscard;
use Override;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

use function Faslatam\PsrFramework\getenv;

final readonly class VerifyEmail implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        private Environment $environment,
        private PHPMailer $mail,
        private Flash $flash,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $redirectGuest = (string) $this->auth->config('redirect.guest');

        /** @var string */
        $token = $request->getQueryParams()['token'] ?? '';

        $user = $this->auth->user();

        if ($user?->isVerified()) {
            $response = $this->responseFactory->createResponse();

            return $response->withHeader('location', $redirectGuest);
        }

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();
                $user = $this->auth->verifyToken($token);

                if ($token && !$user) {
                    $this->flash->set([
                        'El token de verificación es inválido o ha expirado.',
                    ]);

                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader(
                        'location',
                        '/verificar-correo',
                    );
                }

                if ($user?->verifyEmail()) {
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader('location', $redirectGuest);
                }

                $html = $this->environment->render('pages/verify-email.twig', [
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                $token = $user?->generateVerificationToken();
                $email = (string) $user?->email;

                $mailBody = $this->environment->render('emails/verify-email-token.twig', [
                    'appUrl' => getenv('APP_URL'),
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
