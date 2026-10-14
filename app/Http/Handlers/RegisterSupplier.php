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

final readonly class RegisterSupplier implements RequestHandlerInterface
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
        $db = $this->auth->db();

        /** @var array */
        $requestBody = $request->getParsedBody();

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/register-supplier.twig', [
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                $validated = $this->form->validate($requestBody, [
                    'name' => 'string',
                    'tax_id' => 'string',
                    'address' => 'string',
                    'phone' => 'string',
                    'seller' => 'string',
                    'alias' => 'string',
                ]);

                if (!$validated) {
                    $this->flash->set($this->form->errors());
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader(
                        'location',
                        '/proveedores/registrar',
                    );
                }

                $db
                    ->insert('suppliers')
                    ->params($validated)
                    ->unique('name', 'tax_id');

                if (!$db->execute()) {
                    $this->flash->set($db->errors());
                    $response = $this->responseFactory->createResponse();

                    return $response->withHeader(
                        'location',
                        '/proveedores/registrar',
                    );
                }

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/proveedores',
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
