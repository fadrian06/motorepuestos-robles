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
final readonly class RegisterPurchase implements RequestHandlerInterface
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
        $suppliers = array_map(
            static function (array $supplier): array {
                return array_map(static function ($value) {
                    return is_string($value)
                        ? mb_convert_encoding($value, 'UTF-8', 'UTF-8')
                        : $value;
                }, $supplier);
            },
            $db->query('
            SELECT id, name, alias
            FROM suppliers
            ORDER BY name ASC
        ')->all(),
        );

        /** @var array[] */
        $products = array_map(
            static function (array $product): array {
                return array_map(static function ($value) {
                    return is_string($value)
                        ? mb_convert_encoding($value, 'UTF-8', 'UTF-8')
                        : $value;
                }, $product);
            },
            $db->query('
            SELECT id, name, category
            FROM products
            ORDER BY name ASC
        ')->all(),
        );

        $html = $this->environment->render('pages/register-purchase.twig', [
            'flash' => $this->flash->display(),
            'suppliers' => $suppliers ?? [],
            'products' => $products ?? [],
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $body = $request->getParsedBody();

        $validated = $this->form->validate($body, [
            'supplier_id' => 'string',
            'supplier_invoice_code' => 'string',
        ]);

        if (!$validated) {
            $this->flash->set($this->form->errors());

            return $this->redirect('/compras/registrar');
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

            return $this->redirect('/compras/registrar');
        }

        $items = $this->validateItems($productIds, $quantities, $costs);

        if ($items === null) {
            return $this->redirect('/compras/registrar');
        }

        return $this->persistPurchase($validated, $items);
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
     * Inserts the purchase header and its items inside a single transaction.
     *
     * @param array   $header Validated header fields.
     * @param array[] $items  Validated line items.
     */
    private function persistPurchase(
        array $header,
        array $items,
    ): ResponseInterface {
        $db = $this->auth->db();

        /** @var \PDO */
        $pdo = $db->connection();
        $pdo->beginTransaction();

        try {
            $userId = $this->auth->id();

            $db
                ->insert('purchases')
                ->params([
                    'user_id' => $userId,
                    'supplier_id' => $header['supplier_id'],
                    'supplier_invoice_code' => $header['supplier_invoice_code'],
                ])
                ->unique('supplier_invoice_code');

            if (!$db->execute()) {
                $pdo->rollBack();
                $this->flash->set($db->errors());

                return $this->redirect('/compras/registrar');
            }

            $purchaseId = (int) $pdo->lastInsertId();

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

                    return $this->redirect('/compras/registrar');
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->flash->set(['error' => $e->getMessage()]);

            return $this->redirect('/compras/registrar');
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
