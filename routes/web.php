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
use App\Enums\Permission;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\RouterFactory;
use Faslatam\PsrFramework\QueueRequestHandler;
use Faslatam\PsrFramework\Router;
use Leaf\Auth;
use Psr\Container\ContainerInterface;

return new readonly class implements RouterFactory {
    #[Override]
    #[NoDiscard]
    public function __invoke(ContainerInterface $container): Router
    {
        /** @var Authorize */
        $authorize = $container->get(Authorize::class);

        /** @var Auth */
        $auth = $container->get(Auth::class);

        $redirectLogin = $auth->config('redirect.login');
        $redirectGuest = $auth->config('redirect.guest');

        $router = new Router()->setContainer($container);

        $router->attach(
            ['GET'],
            ['#^/$#', "#^{$redirectGuest}$#"],
            new QueueRequestHandler(
                Dashboard::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ["#^{$redirectLogin}$#"],
            new QueueRequestHandler(
                SignIn::class,
                RedirectIfAuthenticated::class,
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/registrarse$#'],
            new QueueRequestHandler(
                SignUp::class,
                RedirectIfAuthenticated::class,
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/salir$#'],
            new QueueRequestHandler(SignOut::class, Authenticate::class),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/verificar-correo$#'],
            new QueueRequestHandler(VerifyEmail::class, Authenticate::class),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/recuperar-clave$#'],
            new QueueRequestHandler(
                ForgotPassword::class,
                RedirectIfAuthenticated::class,
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/restablecer-clave$#'],
            new QueueRequestHandler(
                ResetPassword::class,
                RedirectIfAuthenticated::class,
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/actualizar-clave$#'],
            new QueueRequestHandler(
                UpdatePassword::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/vendedores$#'],
            new QueueRequestHandler(
                ListSellers::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ViewSellers),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/vendedores/registrar$#'],
            new QueueRequestHandler(
                RegisterSeller::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::RegisterSeller),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/vendedores/(?<id>\d+)$#'],
            new QueueRequestHandler(
                ResetPermissions::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ResetPermissions),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/clientes$#'],
            new QueueRequestHandler(
                ListClients::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ViewClients),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/clientes/registrar$#'],
            new QueueRequestHandler(
                RegisterClient::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::RegisterClient),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/clientes/(?<id>\d+)$#'],
            new QueueRequestHandler(
                UpdateClient::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::UpdateClient),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/clientes/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                DeleteClient::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::DeleteClient),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/cotizaciones$#'],
            new QueueRequestHandler(
                UpdateExchangeRates::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::UpdateExchangeRates),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/proveedores$#'],
            new QueueRequestHandler(
                ListSuppliers::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ViewSuppliers),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/proveedores/registrar$#'],
            new QueueRequestHandler(
                RegisterSupplier::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::RegisterSupplier),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/proveedores/(?<id>\d+)$#'],
            new QueueRequestHandler(
                UpdateSupplier::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::UpdateSupplier),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/proveedores/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                DeleteSupplier::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::DeleteSupplier),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/productos$#'],
            new QueueRequestHandler(
                ListProducts::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ViewProducts),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/productos/registrar$#'],
            new QueueRequestHandler(
                RegisterProduct::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::RegisterProduct),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/productos/(?<id>\d+)$#'],
            new QueueRequestHandler(
                UpdateProduct::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::UpdateProduct),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/productos/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                DeleteProduct::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::DeleteProduct),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/compras$#'],
            new QueueRequestHandler(
                ListPurchases::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ViewPurchases),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/compras/registrar$#'],
            new QueueRequestHandler(
                RegisterPurchase::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::RegisterPurchase),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/compras/(?<id>\d+)$#'],
            new QueueRequestHandler(
                UpdatePurchase::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::UpdatePurchase),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/compras/(?<id>\d+)/eliminar$#'],
            new QueueRequestHandler(
                DeletePurchase::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::DeletePurchase),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/compras/(?<id>\d+)/verificar$#'],
            new QueueRequestHandler(
                VerifyPurchase::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::VerifyPurchase),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/ventas$#'],
            new QueueRequestHandler(
                ListSales::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ViewSales),
            ),
        );

        $router->attach(
            ['GET', 'POST'],
            ['#^/ventas/registrar$#'],
            new QueueRequestHandler(
                RegisterSale::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::RegisterSale),
            ),
        );

        $router->attach(
            ['GET'],
            ['#^/ventas/(?<id>\d+)$#'],
            new QueueRequestHandler(
                ViewSale::class,
                Authenticate::class,
                EnsureEmailIsVerified::class,
                $authorize->withPermissions(Permission::ViewSales),
            ),
        );

        return $router;
    }
};
