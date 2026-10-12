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

final readonly class DeleteClient implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $clientId = (int) $request->getAttribute('id');
        $db = $this->auth->db();

        $db->delete('clients');
        $db->where('id', $clientId)->execute();

        $response = $this->responseFactory->createResponse();

        return $response->withHeader('location', '/clientes');
    }
}
