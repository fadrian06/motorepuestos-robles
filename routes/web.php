<?php

declare(strict_types=1);

use App\Http\Handlers\Dashboard;
use App\Http\Handlers\DeleteClient;
use App\Http\Handlers\DeleteProduct;
use App\Http\Handlers\DeletePurchase;
use App\Http\Handlers\DeleteSupplier;
use App\Http\Handlers\ForgotPassword;
use App\Http\Handlers\ListClients;
use App\Http\Handlers\ListProducts;
use App\Http\Handlers\ListPurchases;
use App\Http\Handlers\ListSales;
use App\Http\Handlers\ListSellers;
use App\Http\Handlers\ListSuppliers;
use App\Http\Handlers\RegisterClient;
use App\Http\Handlers\RegisterProduct;
use App\Http\Handlers\RegisterPurchase;
use App\Http\Handlers\RegisterSale;
use App\Http\Handlers\RegisterSeller;
use App\Http\Handlers\RegisterSupplier;
use App\Http\Handlers\ResetPassword;
use App\Http\Handlers\ResetPermissions;
use App\Http\Handlers\SignIn;
use App\Http\Handlers\SignOut;
use App\Http\Handlers\SignUp;
use App\Http\Handlers\UpdateClient;
use App\Http\Handlers\UpdateExchangeRates;
use App\Http\Handlers\UpdatePassword;
use App\Http\Handlers\UpdateProduct;
use App\Http\Handlers\UpdatePurchase;
use App\Http\Handlers\UpdateSupplier;
use App\Http\Handlers\VerifyEmail;
use App\Http\Handlers\VerifyPurchase;
use App\Http\Handlers\ViewSale;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\RouterFactory;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\Router;
use Leaf\Auth;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

return new readonly class implements RouterFactory {
    #[Override]
    #[NoDiscard]
    public function __invoke(ContainerInterface $container): Router
    {
        /** @var MiddlewareInterface */
        $authenticate = $container->get(Authenticate::class);

        /** @var MiddlewareInterface */
        $redirectIfAuthenticated = $container->get(
            RedirectIfAuthenticated::class,
        );

        /** @var MiddlewareInterface */
        $ensureEmailIsVerified = $container->get(EnsureEmailIsVerified::class);

        /** @var Authorize */
        $authorize = $container->get(Authorize::class);

        /** @var RequestHandlerInterface */
        $dashboard = $container->get(Dashboard::class);

        /** @var RequestHandlerInterface */
        $signIn = $container->get(SignIn::class);

        /** @var RequestHandlerInterface */
        $signUp = $container->get(SignUp::class);

        /** @var RequestHandlerInterface */
        $signOut = $container->get(SignOut::class);

        /** @var RequestHandlerInterface */
        $verifyEmail = $container->get(VerifyEmail::class);

        /** @var RequestHandlerInterface */
        $forgotPassword = $container->get(ForgotPassword::class);

        /** @var RequestHandlerInterface */
        $resetPassword = $container->get(ResetPassword::class);

        /** @var RequestHandlerInterface */
        $updatePassword = $container->get(UpdatePassword::class);

        /** @var RequestHandlerInterface */
        $registerSeller = $container->get(RegisterSeller::class);

        /** @var RequestHandlerInterface */
        $listSellers = $container->get(ListSellers::class);

        /** @var RequestHandlerInterface */
        $resetPermissions = $container->get(ResetPermissions::class);

        /** @var RequestHandlerInterface */
        $registerClient = $container->get(RegisterClient::class);

        /** @var RequestHandlerInterface */
        $listClients = $container->get(ListClients::class);

        /** @var RequestHandlerInterface */
        $updateClient = $container->get(UpdateClient::class);

        /** @var RequestHandlerInterface */
        $deleteClient = $container->get(DeleteClient::class);

        /** @var RequestHandlerInterface */
        $updateExchangeRates = $container->get(UpdateExchangeRates::class);

        /** @var RequestHandlerInterface */
        $listSuppliers = $container->get(ListSuppliers::class);

        /** @var RequestHandlerInterface */
        $registerSupplier = $container->get(RegisterSupplier::class);

        /** @var RequestHandlerInterface */
        $updateSupplier = $container->get(UpdateSupplier::class);

        /** @var RequestHandlerInterface */
        $deleteSupplier = $container->get(DeleteSupplier::class);

        /** @var RequestHandlerInterface */
        $listProducts = $container->get(ListProducts::class);

        /** @var RequestHandlerInterface */
        $listPurchases = $container->get(ListPurchases::class);

        /** @var RequestHandlerInterface */
        $listSales = $container->get(ListSales::class);

        /** @var RequestHandlerInterface */
        $viewSale = $container->get(ViewSale::class);

        /** @var RequestHandlerInterface */
        $registerSale = $container->get(RegisterSale::class);

        /** @var RequestHandlerInterface */
        $registerPurchase = $container->get(RegisterPurchase::class);

        /** @var RequestHandlerInterface */
        $registerProduct = $container->get(RegisterProduct::class);

        /** @var RequestHandlerInterface */
        $updateProduct = $container->get(UpdateProduct::class);

        /** @var RequestHandlerInterface */
        $updatePurchase = $container->get(UpdatePurchase::class);

        /** @var RequestHandlerInterface */
        $deleteProduct = $container->get(DeleteProduct::class);

        /** @var RequestHandlerInterface */
        $deletePurchase = $container->get(DeletePurchase::class);

        /** @var RequestHandlerInterface */
        $verifyPurchase = $container->get(VerifyPurchase::class);

        /** @var Auth */
        $auth = $container->get(Auth::class);

        $redirectLogin = $auth->config('redirect.login');
        $redirectGuest = $auth->config('redirect.guest');

        $router = new Router();

        $router->attach(
            ['GET'],
            ['#^/$#', "#^{$redirectGuest}$#"],
            new QueueRequestHandler(
                $dashboard,
                $authenticate,
                $ensureEmailIsVerified,
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ["#^{$redirectLogin}$#"],
            new QueueRequestHandler($signIn, $redirectIfAuthenticated),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/registrarse$#'],
            new QueueRequestHandler($signUp, $redirectIfAuthenticated),
        );

        $router->attach(
            ['GET'],
            ['#^/salir$#'],
            new QueueRequestHandler($signOut, $authenticate),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/verificar-correo$#'],
            new QueueRequestHandler($verifyEmail, $authenticate),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/recuperar-clave$#'],
            new QueueRequestHandler($forgotPassword, $redirectIfAuthenticated),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/restablecer-clave$#'],
            new QueueRequestHandler($resetPassword, $redirectIfAuthenticated),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/actualizar-clave$#'],
            new QueueRequestHandler(
                $updatePassword,
                $authenticate,
                $ensureEmailIsVerified,
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/vendedores$#'],
            new QueueRequestHandler(
                $listSellers,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('view sellers'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/vendedores/registrar$#'],
            new QueueRequestHandler(
                $registerSeller,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('register seller'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/vendedores/(?<id>\d+)$#'],
            new QueueRequestHandler(
                $resetPermissions,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('reset seller permissions'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/clientes$#'],
            new QueueRequestHandler(
                $listClients,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('view clients'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/clientes/registrar$#'],
            new QueueRequestHandler(
                $registerClient,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('register client'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/clientes/(?<id>\d+)$#'],
            new QueueRequestHandler(
                $updateClient,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('update client'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/clientes/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                $deleteClient,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('delete client'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/cotizaciones$#'],
            new QueueRequestHandler(
                $updateExchangeRates,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('update exchange rates'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/proveedores$#'],
            new QueueRequestHandler(
                $listSuppliers,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('view suppliers'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/proveedores/registrar$#'],
            new QueueRequestHandler(
                $registerSupplier,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('register supplier'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/proveedores/(?<id>\d+)$#'],
            new QueueRequestHandler(
                $updateSupplier,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('update supplier'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/proveedores/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                $deleteSupplier,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('delete supplier'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/productos$#'],
            new QueueRequestHandler(
                $listProducts,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('view products'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/productos/registrar$#'],
            new QueueRequestHandler(
                $registerProduct,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('register product'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/productos/(?<id>\d+)$#'],
            new QueueRequestHandler(
                $updateProduct,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('update product'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/productos/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                $deleteProduct,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('delete product'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/compras$#'],
            new QueueRequestHandler(
                $listPurchases,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('view purchases'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/compras/registrar$#'],
            new QueueRequestHandler(
                $registerPurchase,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('register purchase'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/compras/(?<id>\d+)$#'],
            new QueueRequestHandler(
                $updatePurchase,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('update purchase'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/compras/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                $deletePurchase,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('delete purchase'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/compras/(?<id>\d+)/verificar$#'],
            new QueueRequestHandler(
                $verifyPurchase,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('verify purchase'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/ventas$#'],
            new QueueRequestHandler(
                $listSales,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('view sales'),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/ventas/registrar$#'],
            new QueueRequestHandler(
                $registerSale,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('register sale'),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/ventas/(?<id>\d+)$#'],
            new QueueRequestHandler(
                $viewSale,
                $authenticate,
                $ensureEmailIsVerified,
                $authorize->withPermissions('view sales'),
            ),
        );

        return $router;
    }
};
