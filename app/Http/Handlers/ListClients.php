<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class ListClients implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        private Environment $environment,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $db = $this->auth->db();
        $db->select('clients');

        /** @var array[] */
        $clients = $db->all();

        if ($clients) {
            dd($clients);
        }

        $html = $this->environment->render('pages/clients.twig', [
            'clients' => $clients,
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
