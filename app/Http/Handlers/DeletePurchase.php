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

final readonly class DeletePurchase implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Auth $auth,
        private Flash $flash,
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

        // Only unverified purchases can be deleted
        if ($purchase['verified_at'] !== null) {
            $this->flash->set([
                'error' => 'No se puede eliminar una compra que ya ha sido verificada.',
            ]);

            return $this->responseFactory->createResponse()->withHeader(
                'location',
                '/compras',
            );
        }

        /** @var \PDO */
        $pdo = $db->connection();
        $pdo->beginTransaction();

        try {
            // Delete child items first
            $db->delete('purchases_items')->where('purchase_id', $id);
            if (!$db->execute()) {
                $pdo->rollBack();
                $this->flash->set($db->errors());

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/compras',
                );
            }

            // Delete purchase record
            $db->delete('purchases')->where('id', $id);
            if (!$db->execute()) {
                $pdo->rollBack();
                $this->flash->set($db->errors());

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/compras',
                );
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->flash->set(['error' => $e->getMessage()]);

            return $this->responseFactory->createResponse()->withHeader(
                'location',
                '/compras',
            );
        }

        return $this->responseFactory->createResponse()->withHeader(
            'location',
            '/compras',
        );
    }
}
