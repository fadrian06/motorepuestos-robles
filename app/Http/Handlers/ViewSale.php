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

final readonly class ViewSale implements RequestHandlerInterface
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
        $saleId = (int) $request->getAttribute('id');
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
                er.usd_cop
            FROM sales s
            INNER JOIN clients c ON s.client_id = c.id
            INNER JOIN users u ON s.user_id = u.id
            INNER JOIN exchange_rates er ON s.exchange_rate_id = er.id
            WHERE s.id = ' . $saleId)->all();

        $sale = $sales[0] ?? null;

        if (!$sale) {
            return $this->responseFactory->createResponse(404);
        }

        /** @var array[] */
        $items = $db->query('
            SELECT
                si.id,
                si.quantity,
                si.product_price,
                (si.quantity * si.product_price) AS subtotal,
                p.name AS product_name,
                p.category AS product_category,
                s.alias AS supplier_alias,
                pu.supplier_invoice_code
            FROM sales_items si
            INNER JOIN purchases_items pi ON si.purchase_item_id = pi.id
            INNER JOIN products p ON pi.product_id = p.id
            INNER JOIN purchases pu ON pi.purchase_id = pu.id
            INNER JOIN suppliers s ON pu.supplier_id = s.id
            WHERE si.sale_id = '
        . $saleId
        . '
            ORDER BY si.id ASC
        ')->all();

        /** @var array[] */
        $payments = $db->query('
            SELECT
                p.id,
                p.amount,
                p.currency,
                er.usd_ves,
                er.usd_cop
            FROM payments p
            INNER JOIN exchange_rates er ON p.exchange_rate_id = er.id
            WHERE p.sale_id = '
        . $saleId
        . '
            ORDER BY p.id ASC
        ')->all();

        $totalUSD = 0.0;
        foreach ($items as $item) {
            $totalUSD += (float) ($item['subtotal'] ?? 0);
        }

        $rateVES = (float) $sale['usd_ves'];
        $rateCOP = (float) $sale['usd_cop'];

        $totalVES = $totalUSD * $rateVES;
        $totalCOP = $totalUSD * $rateCOP;

        $totalPaidUSD = 0.0;
        foreach ($payments as $payment) {
            $amt = (float) $payment['amount'];
            $curr = strtolower((string) $payment['currency']);

            if ($curr === 'usd') {
                $totalPaidUSD += $amt;
            } elseif ($curr === 'ves' && $rateVES > 0) {
                $totalPaidUSD += $amt / $rateVES;
            } elseif ($curr === 'cop' && $rateCOP > 0) {
                $totalPaidUSD += $amt / $rateCOP;
            }
        }

        $html = $this->environment->render('pages/view-sale.twig', [
            'sale' => $sale,
            'items' => $items,
            'payments' => $payments,
            'totalUSD' => $totalUSD,
            'totalVES' => $totalVES,
            'totalCOP' => $totalCOP,
            'totalPaidUSD' => $totalPaidUSD,
            'flash' => $this->flash->display(),
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
