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

final readonly class RegisterSeller implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Form $form,
        private Flash $flash,
        private Auth $auth,
        private Environment $environment,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $requestBody = $request->getParsedBody();

        $passwordKey = (string) $this->auth->config('password.key');
        $rolesKey = (string) $this->auth->config('roles.key');

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/register-seller.twig', [
                    'passwordKey' => $passwordKey,
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                /** @var false|array<string, string> */
                $validated = $this->form->validate($requestBody, [
                    'email' => 'string',
                    $passwordKey => 'string',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());
                    $response = $this->responseFactory->createResponse(400);

                    return $response->withHeader(
                        'Location',
                        '/vendedores/registrar',
                    );
                }

                if ($this->auth->createUserFor([
                    ...$validated,
                    $rolesKey => json_encode(['seller']),
                    'permissions' => json_encode([]),
                ])) {
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader('location', '/vendedores');
                }

                $this->flash->set($this->auth->errors());
                $response = $this->responseFactory->createResponse();

                return $response->withHeader(
                    'Location',
                    '/vendedores/registrar',
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
