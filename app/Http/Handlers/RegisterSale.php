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

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final readonly class RegisterSale implements RequestHandlerInterface
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
        return match ($request->getMethod()) {
            'GET' => $this->handleGet(),
            'POST' => $this->handlePost($request),
            default => $this->responseFactory->createResponse(405),
        };
    }

    private function handleGet(): ResponseInterface
    {
        $db = $this->auth->db();

        /** @var array[] */
        $clients = $db->query('
            SELECT id, name
            FROM clients
            ORDER BY name ASC
        ')->all();

        /** @var array[] */
        $availableBatches = $db->query('
            SELECT
                pi.id AS purchase_item_id,
                pi.product_id,
                p.name AS product_name,
                p.category AS product_category,
                p.price AS default_price,
                pi.cost,
                s.alias AS supplier_alias,
                pu.supplier_invoice_code,
                (pi.quantity - COALESCE(sold.total_sold, 0) - COALESCE(lost.total_lost, 0)) AS available_quantity
            FROM purchases_items pi
            INNER JOIN purchases pu ON pi.purchase_id = pu.id
            INNER JOIN suppliers s ON pu.supplier_id = s.id
            INNER JOIN products p ON pi.product_id = p.id
            LEFT JOIN (
                SELECT purchase_item_id, SUM(quantity) AS total_sold
                FROM sales_items
                GROUP BY purchase_item_id
            ) sold ON pi.id = sold.purchase_item_id
            LEFT JOIN (
                SELECT purchase_item_id, SUM(quantity) AS total_lost
                FROM products_losses
                GROUP BY purchase_item_id
            ) lost ON pi.id = lost.purchase_item_id
            WHERE pu.verified_at IS NOT NULL
              AND (pi.quantity - COALESCE(sold.total_sold, 0) - COALESCE(lost.total_lost, 0)) > 0
            ORDER BY p.name ASC, pi.id ASC
        ')->all();

        /** @var array[] */
        $latestExchangeRates = $db->query('
            SELECT id, created_at, usd_ves, usd_cop
            FROM exchange_rates
            ORDER BY id DESC
            LIMIT 1
        ')->all();

        $latestRate = $latestExchangeRates[0] ?? null;

        $html = $this->environment->render('pages/register-sale.twig', [
            'flash' => $this->flash->display(),
            'clients' => $clients,
            'availableBatches' => $availableBatches,
            'latestExchangeRate' => $latestRate,
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $body = $request->getParsedBody();

        /** @var false|array<string, string> */
        $validated = $this->form->validate($body, [
            'code' => 'string',
            'client_id' => 'string',
        ]);

        if (!$validated) {
            $this->flash->set($this->form->errors());

            return $this->redirect('/ventas/registrar');
        }

        $code = trim($validated['code']);
        if ($code === '') {
            $this->flash->set(['code' => 'El código de venta es obligatorio.']);

            return $this->redirect('/ventas/registrar');
        }

        $db = $this->auth->db();

        /** @var array[] */
        $latestExchangeRates = $db->query('
            SELECT id, usd_ves, usd_cop
            FROM exchange_rates
            ORDER BY id DESC
            LIMIT 1
        ')->all();

        $exchangeRate = $latestExchangeRates[0] ?? null;
        if ($exchangeRate === null) {
            $this->flash->set([
                'exchange_rate' => 'Debes configurar al menos una tasa de cambio antes de registrar ventas.',
            ]);

            return $this->redirect('/ventas/registrar');
        }

        /** @var string[] */
        $purchaseItemIds = (array) ($body['purchase_item_id'] ?? []);
        /** @var string[] */
        $quantities = (array) ($body['quantity'] ?? []);
        /** @var string[] */
        $productPrices = (array) ($body['product_price'] ?? []);

        if (count($purchaseItemIds) === 0) {
            $this->flash->set([
                'items' => 'Debes agregar al menos un ítem a la venta.',
            ]);

            return $this->redirect('/ventas/registrar');
        }

        $items = $this->validateItems(
            $purchaseItemIds,
            $quantities,
            $productPrices,
        );
        if ($items === null) {
            return $this->redirect('/ventas/registrar');
        }

        /** @var string[] */
        $paymentAmounts = (array) ($body['payment_amount'] ?? []);
        /** @var string[] */
        $paymentCurrencies = (array) ($body['payment_currency'] ?? []);

        $payments = $this->validatePayments(
            $paymentAmounts,
            $paymentCurrencies,
        );
        if ($payments === null) {
            return $this->redirect('/ventas/registrar');
        }

        return $this->persistSale(
            $code,
            (int) $validated['client_id'],
            (int) $exchangeRate['id'],
            $items,
            $payments,
        );
    }

    /**
     * Retrieves current verified stock indexed by purchase_item_id.
     *
     * @return array<int, array{'available_quantity': float, 'verified_at': mixed}>
     */
    private function getBatchesStock(): array
    {
        $db = $this->auth->db();

        /** @var array[] */
        $allBatches = $db->query('
            SELECT
                pi.id AS purchase_item_id,
                pu.verified_at,
                (pi.quantity - COALESCE(sold.total_sold, 0) - COALESCE(lost.total_lost, 0)) AS available_quantity
            FROM purchases_items pi
            INNER JOIN purchases pu ON pi.purchase_id = pu.id
            LEFT JOIN (
                SELECT purchase_item_id, SUM(quantity) AS total_sold
                FROM sales_items
                GROUP BY purchase_item_id
            ) sold ON pi.id = sold.purchase_item_id
            LEFT JOIN (
                SELECT purchase_item_id, SUM(quantity) AS total_lost
                FROM products_losses
                GROUP BY purchase_item_id
            ) lost ON pi.id = lost.purchase_item_id
        ')->all();

        /** @var array<int, array{'available_quantity': float, 'verified_at': mixed}> */
        $batchesStock = [];
        foreach ($allBatches as $batch) {
            $batchesStock[(int) $batch['purchase_item_id']] = [
                'available_quantity' => (float) $batch['available_quantity'],
                'verified_at' => $batch['verified_at'],
            ];
        }

        return $batchesStock;
    }

    /**
     * Validates and returns the line-item array, or null on validation failure.
     *
     * @param  string[] $purchaseItemIds
     * @param  string[] $quantities
     * @param  string[] $productPrices
     * @return array[]|null
     */
    private function validateItems(
        array $purchaseItemIds,
        array $quantities,
        array $productPrices,
    ): ?array {
        $batchesStock = $this->getBatchesStock();
        $items = [];

        /** @var array<int, float> */
        $requestedQuantitiesByBatch = [];

        foreach ($purchaseItemIds as $i => $purchaseItemId) {
            $lineValidated = $this->form->validate([
                'purchase_item_id' => $purchaseItemId,
                'quantity' => $quantities[$i] ?? '',
                'product_price' => $productPrices[$i] ?? '',
            ], [
                'purchase_item_id' => 'string',
                'quantity' => 'string',
                'product_price' => 'string',
            ]);

            if (!$lineValidated) {
                $this->flash->set($this->form->errors());

                return null;
            }

            $batchId = (int) $lineValidated['purchase_item_id'];
            $qty = (float) $lineValidated['quantity'];
            $price = (float) $lineValidated['product_price'];

            if ($qty <= 0 || $price <= 0) {
                $this->flash->set([
                    'items' => 'La cantidad y precio de cada ítem deben ser mayores a cero.',
                ]);

                return null;
            }

            $batchData = $batchesStock[$batchId] ?? null;
            if ($batchData === null || $batchData['verified_at'] === null) {
                $this->flash->set([
                    'items' => "El lote #{$batchId} no existe o no ha sido verificado.",
                ]);

                return null;
            }

            $currentRequested =
                ($requestedQuantitiesByBatch[$batchId] ?? 0.0) + $qty;
            if ($currentRequested > $batchData['available_quantity']) {
                $avail = $batchData['available_quantity'];
                $this->flash->set([
                    'items' => "Stock insuficiente en el lote #{$batchId}. Solicitado: {$currentRequested}, disponible: {$avail}.",
                ]);

                return null;
            }

            $requestedQuantitiesByBatch[$batchId] = $currentRequested;

            $items[] = [
                'purchase_item_id' => $batchId,
                'quantity' => $qty,
                'product_price' => $price,
            ];
        }

        return $items;
    }

    /**
     * Validates payment rows if present.
     *
     * @param  string[] $amounts
     * @param  string[] $currencies
     * @return array[]|null
     */
    private function validatePayments(array $amounts, array $currencies): ?array
    {
        $payments = [];

        foreach ($amounts as $i => $rawAmount) {
            $amountStr = trim((string) $rawAmount);
            if ($amountStr === '') {
                continue;
            }

            $currency = strtolower(trim($currencies[$i] ?? ''));
            if (!in_array($currency, ['usd', 'cop', 'ves'], strict: true)) {
                $this->flash->set([
                    'payment' => 'Moneda de pago inválida (debe ser USD, COP o VES).',
                ]);

                return null;
            }

            $amount = (float) $amountStr;
            if ($amount <= 0) {
                $this->flash->set([
                    'payment' => 'El monto del pago debe ser mayor a 0.',
                ]);

                return null;
            }

            $payments[] = [
                'amount' => $amount,
                'currency' => $currency,
            ];
        }

        return $payments;
    }

    /**
     * Inserts the sale header, its items, and payments inside a single database transaction.
     *
     * @param array[] $items
     * @param array[] $payments
     */
    private function persistSale(
        string $code,
        int $clientId,
        int $exchangeRateId,
        array $items,
        array $payments,
    ): ResponseInterface {
        $db = $this->auth->db();

        /** @var \PDO */
        $pdo = $db->connection();
        $pdo->beginTransaction();

        try {
            $userId = $this->auth->id();

            $db
                ->insert('sales')
                ->params([
                    'code' => $code,
                    'client_id' => $clientId,
                    'user_id' => $userId,
                    'exchange_rate_id' => $exchangeRateId,
                ])
                ->unique('code');

            if (!$db->execute()) {
                $pdo->rollBack();
                $this->flash->set($db->errors());

                return $this->redirect('/ventas/registrar');
            }

            $saleId = (int) $pdo->lastInsertId();

            foreach ($items as $item) {
                $db->insert('sales_items')->params([
                    'sale_id' => $saleId,
                    'purchase_item_id' => $item['purchase_item_id'],
                    'product_price' => $item['product_price'],
                    'quantity' => $item['quantity'],
                ]);

                if (!$db->execute()) {
                    $pdo->rollBack();
                    $this->flash->set($db->errors());

                    return $this->redirect('/ventas/registrar');
                }
            }

            foreach ($payments as $payment) {
                $db->insert('payments')->params([
                    'sale_id' => $saleId,
                    'amount' => $payment['amount'],
                    'currency' => $payment['currency'],
                    'exchange_rate_id' => $exchangeRateId,
                ]);

                if (!$db->execute()) {
                    $pdo->rollBack();
                    $this->flash->set($db->errors());

                    return $this->redirect('/ventas/registrar');
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->flash->set(['error' => $e->getMessage()]);

            return $this->redirect('/ventas/registrar');
        }

        return $this->redirect('/ventas');
    }

    private function redirect(string $location): ResponseInterface
    {
        return $this->responseFactory->createResponse()->withHeader(
            'location',
            $location,
        );
    }
}
