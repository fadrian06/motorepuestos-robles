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

final readonly class VerifyPurchase implements RequestHandlerInterface
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

        // Check if already verified
        if ($purchase['verified_at'] !== null) {
            $this->flash->set([
                'error' => 'Esta compra ya ha sido verificada previamente.',
            ]);

            return $this->responseFactory->createResponse()->withHeader(
                'location',
                '/compras',
            );
        }

        $now = date('Y-m-d H:i:s');
        $createdAt = (string) $purchase['created_at'];

        // Enforce verified_at >= created_at constraint
        $verifiedAt = $now < $createdAt ? $createdAt : $now;

        $db
            ->update('purchases')
            ->params(['verified_at' => $verifiedAt])
            ->where('id', $id);

        if (!$db->execute()) {
            $this->flash->set($db->errors());

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
