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

final readonly class UpdateClient implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        private Environment $environment,
        private Flash $flash,
        private Form $form,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $clientId = (int) $request->getAttribute('id');
        $db = $this->auth->db();

        $db->select('clients');

        /** @var false|array */
        $client = $db->find($clientId);

        if (!$client) {
            return $this->responseFactory->createResponse(404);
        }

        /** @var array */
        $requestBody = $request->getParsedBody();

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/update-client.twig', [
                    'client' => $client,
                    'flash' => $flash,
                ]);

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
                        "/clientes/{$clientId}",
                    );
                }

                $db->update('clients')->params($validated);
                $db->where('id', $clientId);

                if ($db->execute()) {
                    return $this->responseFactory->createResponse()->withHeader(
                        'location',
                        '/clientes',
                    );
                }

                $this->flash->set($db->errors());

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    "/clientes/{$clientId}",
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
