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

final readonly class ListProducts implements RequestHandlerInterface
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
        $db->select('products');

        /** @var array[] */
        $products = array_map(static function (array $product): array {
            return array_map(static function ($value) {
                return is_string($value)
                    ? mb_convert_encoding($value, 'UTF-8', 'UTF-8')
                    : $value;
            }, $product);
        }, $db->all());

        $html = $this->environment->render('pages/products.twig', [
            'products' => $products,
        ]);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($html);

        return $response;
    }
}
