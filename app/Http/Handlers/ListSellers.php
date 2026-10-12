<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use Leaf\Db;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class ListSellers implements RequestHandlerInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory, private Auth $auth, private Environment $environment) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string */
        $authTable = $this->auth->config('db.table');

        /** @var string */
        $rolesKey = $this->auth->config('roles.key');

        /** @var Db */
        $db = $this->auth->db()->select($authTable);

        /** @var array[] */
        $sellers = $db->where($rolesKey, 'LIKE', '%seller%')->all();

        if ($sellers) {
            dd($sellers);
        }

        $html = $this->environment->render('pages/sellers.twig', [
            'sellers' => $sellers,
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
