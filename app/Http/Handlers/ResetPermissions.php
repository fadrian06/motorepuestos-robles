<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use App\Enums\Permission;
use Leaf\Auth;
use Leaf\Db;
use Leaf\Flash;
use NoDiscard;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class ResetPermissions implements RequestHandlerInterface
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
        $sellerId = (int) $request->getAttribute('id');

        /** @var array */
        $requestBody = $request->getParsedBody();

        $authTable = (string) $this->auth->config('db.table');
        $rolesKey = (string) $this->auth->config('roles.key');
        $authIdKey = (string) $this->auth->config('id.key');
        $db = $this->auth->db();

        $db->select($authTable);

        /** @var false|array */
        $seller = $db
            ->where($rolesKey, 'LIKE', '%seller%')
            ->where($authIdKey, $sellerId)
            ->first();

        if (!$seller) {
            return $this->responseFactory->createResponse(404);
        }

        $availablePermissions = array_map(
            static fn(Permission $permission): array => [
                'key' => $permission->key(),
                'name' => $permission->name(),
                'desc' => $permission->description(),
                'category' => $permission->category(),
            ],
            Permission::cases(),
        );

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $html = $this->environment->render('pages/reset-permissions.twig', [
                    'seller' => $seller,
                    'flash' => $flash,
                    'availablePermissions' => $availablePermissions,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                /** @var array */
                $permissions = $requestBody['permissions'] ?? [];

                $this->auth
                    ->db()
                    ->update($authTable)
                    ->params([
                        'permissions' => json_encode($permissions),
                    ])
                    ->where($authIdKey, $sellerId)
                    ->execute();

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/vendedores',
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
