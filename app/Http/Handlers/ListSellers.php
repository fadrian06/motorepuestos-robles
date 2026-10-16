<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use Leaf\Db;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class ListSellers implements RequestHandlerInterface
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
        $authTable = (string) $this->auth->config('db.table');
        $rolesKey = (string) $this->auth->config('roles.key');
        $db = $this->auth->db();

        $db->select($authTable);

        /** @var array[] */
        $sellers = $db->where($rolesKey, 'LIKE', '%seller%')->all();

        $html = $this->environment->render('pages/sellers.twig', [
            'sellers' => $sellers,
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
