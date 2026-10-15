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

final readonly class RegisterProduct implements RequestHandlerInterface
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

                $html = $this->environment->render('pages/register-product.twig', [
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                $validated = $this->form->validate($requestBody, [
                    'name' => 'string',
                    'category' => 'string',
                    'price' => 'number',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());

                    return $this->responseFactory->createResponse()->withHeader(
                        'location',
                        '/productos/registrar',
                    );
                }

                $db->insert('products')->params($validated)->unique('name');

                if ($db->execute()) {
                    return $this->responseFactory->createResponse()->withHeader(
                        'location',
                        '/productos',
                    );
                }

                $this->flash->set($db->errors());

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/productos/registrar',
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
