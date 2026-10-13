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

final readonly class UpdateExchangeRates implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Flash $flash,
        private Environment $environment,
        private Auth $auth,
        private Form $form,
    ) {}

    #[Override]
    #[NoDiscard]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $db = $this->auth->db();

        /** @var array */
        $requestBody = $request->getParsedBody();

        switch ($request->getMethod()) {
            case 'GET':
                $flash = $this->flash->display();

                $db->select('exchange_rates');

                /** @var false|array */
                $exchangeRate = $db->last();

                $html = $this->environment->render('pages/update-exchange-rates.twig', [
                    'exchangeRate' => $exchangeRate,
                    'flash' => $flash,
                ]);

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($html);

                return $response;

            case 'POST':
                $db->insert('exchange_rates')->params([
                    'usd_ves' => $requestBody['usd_ves'],
                    'usd_cop' => $requestBody['usd_cop'],
                ]);

                if ($db->execute()) {
                    return $this->responseFactory->createResponse()->withHeader(
                        'location',
                        '/cotizaciones',
                    );
                }

                $this->flash->set($db->errors());

                return $this->responseFactory->createResponse()->withHeader(
                    'location',
                    '/cotizaciones',
                );

            default:
                return $this->responseFactory->createResponse(405);
        }
    }
}
