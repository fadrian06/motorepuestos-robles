<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use Leaf\Auth;
use Leaf\Flash;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class ListSales implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        private Environment $environment,
        private Flash $flash,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $db = $this->auth->db();

        /** @var array[] */
        $sales = $db->query('
            SELECT
                s.id,
                s.code,
                s.created_at,
                c.name AS client_name,
                u.email AS seller_email,
                er.usd_ves,
                er.usd_cop,
                COALESCE(SUM(si.quantity * si.product_price), 0) AS total_usd,
                COUNT(si.id) AS items_count
            FROM sales s
            INNER JOIN clients c ON s.client_id = c.id
            INNER JOIN users u ON s.user_id = u.id
            INNER JOIN exchange_rates er ON s.exchange_rate_id = er.id
            LEFT JOIN sales_items si ON s.id = si.sale_id
            GROUP BY s.id
            ORDER BY s.id DESC
        ')->all();

        $html = $this->environment->render('pages/sales.twig', [
            'sales' => $sales ?? [],
            'flash' => $this->flash->display(),
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
