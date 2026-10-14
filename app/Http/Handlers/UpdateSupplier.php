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

final readonly class UpdateSupplier implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Flash $flash,
        private Auth $auth,
        private Form $form,
        private Environment $environment,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $db = $this->auth->db();

        /** @var array */
        $requestBody = $request->getParsedBody();

        $supplierId = (int) $request->getAttribute('id');

        $db->select('suppliers');

        /** @var false|array */
        $supplier = $db->find($supplierId);

        if (!$supplier) {
            return $this->responseFactory->createResponse(404);
        }

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/update-supplier.twig', [
                    'supplier' => $supplier,
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

                    return $this->responseFactory->createResponse(
                        400,
                    )->withHeader('location', "/proveedores/{$supplierId}");
                }

                $db
                    ->update('suppliers')
                    ->params($validated)
                    ->where('id', $supplierId);

                if (!$db->execute()) {
                    $this->flash->set($db->errors());

                    return $this->responseFactory->createResponse()->withHeader(
                        'location',
                        "/proveedores/{$supplierId}",
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
