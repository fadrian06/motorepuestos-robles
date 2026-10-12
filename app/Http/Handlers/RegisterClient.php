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

final readonly class RegisterClient implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Environment $environment,
        private Form $form,
        private Flash $flash,
        private Auth $auth,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $requestBody = $request->getParsedBody();

        $db = $this->auth->db();

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render(
                    'pages/register-client.twig',
                    [
                        'flash' => $flash,
                    ],
                );

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                $validated = $this->form->validate($requestBody, [
                    'name' => 'string',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader(
                        'location',
                        '/clientes/registrar',
                    );
                }

                $db->insert('clients')->params($validated)->unique('name');

                if ($db->execute()) {
                    return $this->responseFactory->createResponse()->withHeader(
                        'location',
                        '/clientes',
                    );
                }

                $this->flash->set($db->errors());

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/clientes/registrar',
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
