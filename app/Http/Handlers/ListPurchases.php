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

final readonly class ListPurchases implements RequestHandlerInterface
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

        /** @var array[] */
        $purchases = $db->query('
            SELECT
                p.id,
                p.supplier_invoice_code,
                p.created_at,
                p.verified_at,
                s.name  AS supplier_name,
                s.alias AS supplier_alias
            FROM purchases p
            INNER JOIN suppliers s ON p.supplier_id = s.id
            ORDER BY p.id DESC
        ')->all();

        $html = $this->environment->render('pages/purchases.twig', [
            'purchases' => $purchases ?? [],
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
