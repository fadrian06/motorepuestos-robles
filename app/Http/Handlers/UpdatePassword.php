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

final readonly class UpdatePassword implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Flash $flash,
        private Environment $environment,
        private Auth $auth,
        private Form $form,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $passwordKey = (string) $this->auth->config('password.key');
        $redirectGuest = (string) $this->auth->config('redirect.guest');

        /** @var array */
        $requestBody = $request->getParsedBody();

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/update-password.twig', [
                    'passwordKey' => $passwordKey,
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                /** @var false|array<string, string> */
                $validated = $this->form->validate($requestBody, [
                    "old_{$passwordKey}" => 'string',
                    "new_{$passwordKey}" => 'string',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());
                    $response = $this->responseFactory->createResponse(400);

                    return $response->withHeader(
                        'location',
                        '/actualizar-clave',
                    );
                }

                if ($this->auth->updatePassword(
                    $validated["old_{$passwordKey}"],
                    $validated["new_{$passwordKey}"],
                )) {
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader('location', $redirectGuest);
                }

                $this->flash->set($this->auth->errors());
                $response = $this->responseFactory->createResponse();

                return $response->withHeader('location', '/actualizar-clave');

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
