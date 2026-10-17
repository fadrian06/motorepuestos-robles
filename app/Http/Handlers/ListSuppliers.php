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

final readonly class ListSuppliers implements RequestHandlerInterface
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
        $db->select('suppliers');

        /** @var array[] */
        $suppliers = array_map(static function (array $supplier): array {
            return array_map(static function ($value) {
                return is_string($value)
                    ? mb_convert_encoding($value, 'UTF-8', 'UTF-8')
                    : $value;
            }, $supplier);
        }, $db->all());

        $html = $this->environment->render('pages/suppliers.twig', [
            'suppliers' => $suppliers,
            'flash' => $this->flash,
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
