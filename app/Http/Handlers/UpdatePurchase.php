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

/** @mago-expect lint:cyclomatic-complexity */
final readonly class UpdatePurchase implements RequestHandlerInterface
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
        $id = (int) $request->getAttribute('id');
        $db = $this->auth->db();

        $db->select('purchases');

        /** @var false|array */
        $purchase = $db->find($id);

        if (!$purchase) {
            return $this->responseFactory->createResponse(404);
        }

        // Only unverified purchases can be updated
        if ($purchase['verified_at'] !== null) {
            $this->flash->set([
                'error' => 'No se puede modificar una compra que ya ha sido verificada.',
            ]);

            return $this->redirect('/compras');
        }

        return match ($request->getMethod()) {
            'GET' => $this->handleGet($purchase),
            'POST' => $this->handlePost($request, $purchase),
            default => $this->responseFactory->createResponse(405),
        };
    }

    private function handleGet(array $purchase): ResponseInterface
    {
        $db = $this->auth->db();

        /** @var array[] */
        $suppliers = $db->query('
            SELECT id, name, alias
            FROM suppliers
            ORDER BY name ASC
        ')->all();

        /** @var array[] */
        $products = $db->query('
            SELECT id, name, category
            FROM products
            ORDER BY name ASC
        ')->all();

        /** @var array[] */
        $items = $db->query('
            SELECT id, product_id, quantity, cost
            FROM purchases_items
            WHERE purchase_id = '
        . (int) $purchase['id']
        . '
            ORDER BY id ASC
        ')->all();

        $html = $this->environment->render('pages/update-purchase.twig', [
            'purchase' => $purchase,
            'items' => $items ?? [],
            'suppliers' => $suppliers ?? [],
            'products' => $products ?? [],
            'flash' => $this->flash->display(),
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }

    private function handlePost(
        ServerRequestInterface $request,
        array $purchase,
    ): ResponseInterface {
        $purchaseId = (int) $purchase['id'];

        /** @var array */
        $body = $request->getParsedBody();

        $validated = $this->form->validate($body, [
            'supplier_id' => 'string',
            'supplier_invoice_code' => 'string',
        ]);

        if (!$validated) {
            $this->flash->set($this->form->errors());

            return $this->redirect("/compras/{$purchaseId}");
        }

        /** @var string[] */
        $productIds = (array) ($body['product_id'] ?? []);
        /** @var string[] */
        $quantities = (array) ($body['quantity'] ?? []);
        /** @var string[] */
        $costs = (array) ($body['cost'] ?? []);

        if (count($productIds) === 0) {
            $this->flash->set([
                'items' => 'Debes agregar al menos un ítem a la compra.',
            ]);

            return $this->redirect("/compras/{$purchaseId}");
        }

        $items = $this->validateItems($productIds, $quantities, $costs);

        if ($items === null) {
            return $this->redirect("/compras/{$purchaseId}");
        }

        return $this->persistUpdate($purchaseId, $validated, $items);
    }

    /**
     * Validates and returns the line-item array, or null on validation failure.
     *
     * @param  string[] $productIds
     * @param  string[] $quantities
     * @param  string[] $costs
     * @return array[]|null
     */
    private function validateItems(
        array $productIds,
        array $quantities,
        array $costs,
    ): ?array {
        $items = [];

        foreach ($productIds as $i => $productId) {
            $lineValidated = $this->form->validate([
                'product_id' => $productId,
                'quantity' => $quantities[$i] ?? '',
                'cost' => $costs[$i] ?? '',
            ], [
                'product_id' => 'string',
                'quantity' => 'string',
                'cost' => 'string',
            ]);

            if (!$lineValidated) {
                $this->flash->set($this->form->errors());

                return null;
            }

            $items[] = $lineValidated;
        }

        return $items;
    }

    /**
     * Updates the purchase header and replaces its line items inside a single transaction.
     *
     * @param int     $purchaseId ID of the purchase to update.
     * @param array   $header     Validated header fields.
     * @param array[] $items      Validated line items.
     */
    private function persistUpdate(
        int $purchaseId,
        array $header,
        array $items,
    ): ResponseInterface {
        $db = $this->auth->db();

        /** @var \PDO */
        $pdo = $db->connection();
        $pdo->beginTransaction();

        try {
            // Update purchase header
            $db
                ->update('purchases')
                ->params([
                    'supplier_id' => $header['supplier_id'],
                    'supplier_invoice_code' => $header['supplier_invoice_code'],
                ])
                ->where('id', $purchaseId);

            if (!$db->execute()) {
                $pdo->rollBack();
                $this->flash->set($db->errors());

                return $this->redirect("/compras/{$purchaseId}");
            }

            // Remove previous items
            $db->delete('purchases_items')->where('purchase_id', $purchaseId);

            if (!$db->execute()) {
                $pdo->rollBack();
                $this->flash->set($db->errors());

                return $this->redirect("/compras/{$purchaseId}");
            }

            // Re-insert line items
            foreach ($items as $item) {
                $db->insert('purchases_items')->params([
                    'purchase_id' => $purchaseId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost' => $item['cost'],
                ]);

                if (!$db->execute()) {
                    $pdo->rollBack();
                    $this->flash->set($db->errors());

                    return $this->redirect("/compras/{$purchaseId}");
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->flash->set(['error' => $e->getMessage()]);

            return $this->redirect("/compras/{$purchaseId}");
        }

        return $this->redirect('/compras');
    }

    private function redirect(string $location): ResponseInterface
    {
        return $this->responseFactory->createResponse()->withHeader(
            'location',
            $location,
        );
    }
}
