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
        };
    }

    public static function keys(): array
    {
        return array_map(static fn(Permission $permission) => $permission->key(), self::cases());
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
        };
    }

    public function description(): string
    {
        return match ($this) {
            Permission::ViewSellers => 'Permite visualizar el listado del equipo de vendedores.',
            Permission::RegisterSeller => 'Permite crear cuentas para nuevos vendedores.',
            Permission::ResetPermissions => 'Permite modificar permisos de otros usuarios.',
            Permission::ViewClients => 'Permite consultar el listado y directorio de clientes.',
            Permission::RegisterClient => 'Permite dar de alta a nuevos compradores.',
            Permission::UpdateClient => 'Permite editar la información de clientes.',
            Permission::DeleteClient => 'Permite borrar registros de clientes del sistema.',
            Permission::UpdateExchangeRates => 'Permite actualizar las tasas de cambio USD/VES y USD/COP.',
        };
    }

    public function category(): string
    {
        return match ($this) {
            Permission::ViewSellers, Permission::RegisterSeller, Permission::ResetPermissions => 'Vendedores',
            Permission::ViewClients, Permission::RegisterClient, Permission::UpdateClient, Permission::DeleteClient => 'Clientes',
            Permission::UpdateExchangeRates => 'Cotizaciones',
        };
    }
}
