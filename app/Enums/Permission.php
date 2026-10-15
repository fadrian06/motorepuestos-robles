<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission
{
    case ViewSellers;
    case RegisterSeller;
    case ResetPermissions;
    case ViewClients;
    case RegisterClient;
    case UpdateClient;
    case DeleteClient;
    case UpdateExchangeRates;
    case ViewSuppliers;
    case RegisterSupplier;
    case UpdateSupplier;
    case DeleteSupplier;
    case ViewProducts;
    case RegisterProduct;
    case UpdateProduct;
    case DeleteProduct;
    case ViewPurchases;

    public function key(): string
    {
        return match ($this) {
            Permission::ViewSellers => 'view sellers',
            Permission::RegisterSeller => 'register seller',
            Permission::ResetPermissions => 'reset seller permissions',
            Permission::ViewClients => 'view clients',
            Permission::RegisterClient => 'register client',
            Permission::UpdateClient => 'update client',
            Permission::DeleteClient => 'delete client',
            Permission::UpdateExchangeRates => 'update exchange rates',
            Permission::ViewSuppliers => 'view suppliers',
            Permission::RegisterSupplier => 'register supplier',
            Permission::UpdateSupplier => 'update supplier',
            Permission::DeleteSupplier => 'delete supplier',
            Permission::ViewProducts => 'view products',
            Permission::RegisterProduct => 'register product',
            Permission::UpdateProduct => 'update product',
            Permission::DeleteProduct => 'delete product',
            Permission::ViewPurchases => 'view purchases',
        };
    }

    public static function keys(): array
    {
        return array_map(
            static fn(Permission $permission) => $permission->key(),
            self::cases(),
        );
    }

    public function name(): string
    {
        return match ($this) {
            Permission::ViewSellers => 'Ver Vendedores',
            Permission::RegisterSeller => 'Registrar Vendedores',
            Permission::ResetPermissions => 'Gestionar Permisos',
            Permission::ViewClients => 'Ver Clientes',
            Permission::RegisterClient => 'Registrar Clientes',
            Permission::UpdateClient => 'Modificar Clientes',
            Permission::DeleteClient => 'Eliminar Clientes',
            Permission::UpdateExchangeRates => 'Actualizar Tasas de Cambio',
            Permission::ViewSuppliers => 'Ver Proveedores',
            Permission::RegisterSupplier => 'Registrar Proveedores',
            Permission::UpdateSupplier => 'Modificar Proveedores',
            Permission::DeleteSupplier => 'Eliminar Proveedores',
            Permission::ViewProducts => 'Ver Catálogo de Productos',
            Permission::RegisterProduct => 'Registrar Productos',
            Permission::UpdateProduct => 'Modificar Productos',
            Permission::DeleteProduct => 'Eliminar Productos',
            Permission::ViewPurchases => 'Ver Compras',
        };
    }

    public function description(): string
    {
        return match ($this) {
            Permission::ViewSellers
                => 'Permite visualizar el listado del equipo de vendedores.',
            Permission::RegisterSeller
                => 'Permite crear cuentas para nuevos vendedores.',
            Permission::ResetPermissions
                => 'Permite modificar permisos de otros usuarios.',
            Permission::ViewClients
                => 'Permite consultar el listado y directorio de clientes.',
            Permission::RegisterClient
                => 'Permite dar de alta a nuevos compradores.',
            Permission::UpdateClient
                => 'Permite editar la información de clientes.',
            Permission::DeleteClient
                => 'Permite borrar registros de clientes del sistema.',
            Permission::UpdateExchangeRates
                => 'Permite actualizar las tasas de cambio USD/VES y USD/COP.',
            Permission::ViewSuppliers
                => 'Permite visualizar el listado de proveedores.',
            Permission::RegisterSupplier
                => 'Permite registrar nuevos proveedores.',
            Permission::UpdateSupplier
                => 'Permite actualizar la información de proveedores.',
            Permission::DeleteSupplier
                => 'Permite eliminar proveedores del sistema.',
            Permission::ViewProducts
                => 'Permite consultar el catálogo de productos y sus precios.',
            Permission::RegisterProduct
                => 'Permite dar de alta nuevos productos al catálogo.',
            Permission::UpdateProduct
                => 'Permite modificar los datos y precios de los productos.',
            Permission::DeleteProduct
                => 'Permite eliminar productos del catálogo.',
            Permission::ViewPurchases
                => 'Permite consultar el historial de compras por lote.',
        };
    }

    public function category(): string
    {
        return match ($this) {
            Permission::ViewSellers,
            Permission::RegisterSeller,
            Permission::ResetPermissions,
                => 'Vendedores',
            Permission::ViewClients,
            Permission::RegisterClient,
            Permission::UpdateClient,
            Permission::DeleteClient,
                => 'Clientes',
            Permission::UpdateExchangeRates => 'Cotizaciones',
            Permission::ViewSuppliers,
            Permission::RegisterSupplier,
            Permission::UpdateSupplier,
            Permission::DeleteSupplier,
                => 'Proveedores',
            Permission::ViewProducts,
            Permission::RegisterProduct,
            Permission::UpdateProduct,
            Permission::DeleteProduct,
                => 'Productos',
            Permission::ViewPurchases => 'Compras',
        };
    }
}
