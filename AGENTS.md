# Motorepuestos Robles - Guía y Contexto para Agentes (AGENTS.md)

Sistema web de gestión y venta de repuestos automotrices con control de inventario por lote y trazabilidad de compras a clientes.

---

## 1. Stack Tecnológico & Arquitectura

- **Lenguaje:** PHP 8.4+ (`declare(strict_types=1);`, `#[Override]`, `#[NoDiscard]`, constructor promotion readonly).
- **Framework Base:** `faslatam/psr-framework` (PSR-7 HTTP Messages, PSR-15 `RequestHandlerInterface`, `QueueRequestHandler`, `Router` con regex).
- **Contenedor DI:** `illuminate/container`.
- **Autenticación y Base de Datos:** `leafs/auth` (`Leaf\Auth`, `Leaf\Form`, `Leaf\Flash`, Leaf DB query builder).
- **Motor de Vistas:** `twig/twig` (`Twig\Environment`), plantillas en `resources/views/pages/` y `resources/views/emails/`.
- **Frontend UI:**
  - CSS: `resources/styles/w3.css` con variables CSS propias de marca (`--brand-red: #dc2626;`, `--brand-black: #0d0d11;`, `--w3-surface`, etc.).
  - Interactividad: Alpine.js (`resources/scripts/alpinejs/dist/cdn.min.js`) para búsqueda en vivo, modales y alternador de tema oscuro (`w3-dark-mode`).
  - Layout estándar: Sidebar persistente izquierdo (`.app-sidebar`) + Topbar superior (`.app-topbar`) + Main content responsivo (`.app-main`).
- **Herramientas de Calidad:**
  - Linter & Análisis Estático: Carthage Mago (`composer lint` ejecuta `mago lint`, `mago analyze`, `mago guard`).
  - Formateo: `composer format` (`mago format`).
  - Tests: PHPUnit (`composer test`).

---

## 2. Estructura del Código

```text
motorepuestos-robles/
├── app/
│   ├── Enums/
│   │   └── Permission.php             # Enums de permisos del sistema (spatie-like)
│   ├── Http/
│   │   ├── Handlers/                  # Handlers PSR-15 individuales por acción (Single Responsibility)
│   │   └── Middlewares/               # Middlewares (Authenticate, Authorize, etc.)
│   └── Migration.php                  # Contrato base de migraciones
├── database/
│   └── migrations/                    # Scripts numerados (ej: 04_create_suppliers_table.php)
├── resources/
│   ├── styles/                        # w3.css
│   ├── scripts/                       # alpinejs
│   └── views/
│       ├── emails/                    # Plantillas de correos
│       └── pages/                     # Vistas Twig del sistema
└── routes/
    └── web.php                        # Definición central de rutas y pipelines de middlewares
```

---

## 3. Módulos y Rutas Implementadas

| Módulo | Rutas | Handlers | Vistas Twig |
| :--- | :--- | :--- | :--- |
| **Dashboard** | `GET /` | `Dashboard` | `resources/views/pages/dashboard.twig` |
| **Proveedores** | `GET /proveedores`<br>`GET,POST /proveedores/registrar`<br>`GET,POST /proveedores/{id}`<br>`GET /proveedores/{id}/eliminar` | `ListSuppliers`<br>`RegisterSupplier`<br>`UpdateSupplier`<br>`DeleteSupplier` | `pages/suppliers.twig`<br>`pages/register-supplier.twig`<br>`pages/update-supplier.twig` |
| **Clientes** | `GET /clientes`<br>`GET,POST /clientes/registrar`<br>`GET,POST /clientes/{id}`<br>`GET /clientes/{id}/eliminar` | `ListClients`<br>`RegisterClient`<br>`UpdateClient`<br>`DeleteClient` | `pages/clients.twig`<br>`pages/register-client.twig`<br>`pages/update-client.twig` |
| **Vendedores** | `GET /vendedores`<br>`GET,POST /vendedores/registrar` | `ListSellers`<br>`RegisterSeller` | `pages/sellers.twig`<br>`pages/register-seller.twig` |
| **Cotizaciones** | `GET,POST /cotizaciones` | `UpdateExchangeRates` | `pages/update-exchange-rates.twig` |
| **Seguridad & Auth** | `/entrar`, `/salir`, `/registrarse`, `/verificar-correo`, `/recuperar-clave`, `/restablecer-clave`, `/actualizar-clave` | Handlers en `app/Http/Handlers/` | `signin.twig`, `signup.twig`, `update-password.twig`, etc. |
| **En Progreso** | Inventario por lote (`#inventario`), POS (`#pos`) | Pendientes | Pendientes |

---

## 4. Esquema de Datos Clave

### Tabla `suppliers`:
- `id`: INTEGER PRIMARY KEY AUTOINCREMENT
- `name`: TEXT NOT NULL UNIQUE (Razón Social o Nombre Comercial)
- `tax_id`: TEXT NOT NULL UNIQUE (RIF / Identificación Fiscal)
- `address`: TEXT NOT NULL (Dirección física / Sede)
- `phone`: TEXT NOT NULL (Teléfono)
- `seller`: TEXT NOT NULL (Vendedor o Asesor asignado)
- `alias`: TEXT NOT NULL (Nombre corto para lotes)

### Tabla `clients`:
- `id`: INTEGER PRIMARY KEY AUTOINCREMENT
- `name`: TEXT NOT NULL UNIQUE

---

## 5. Convenciones de Desarrollo

1. **Handlers PSR-15:**
   - Un handler por cada caso de uso o formulario.
   - Declarar siempre `declare(strict_types=1);`, `final readonly class` implementando `RequestHandlerInterface`.
   - Utilizar atributos `#[Override]` y `#[NoDiscard]`.
   - Inyectar dependencias por constructor: `ResponseFactoryInterface`, `Auth`, `Environment`, `Flash`, `Form`.
   - En peticiones `POST`, validar datos con `$this->form->validate($body, [...])`.
   - En caso de error, guardar en flash con `$this->flash->set(...)` y responder con redirect header `location`.
   - **Nunca dejar funciones de depuración como `dd()` o `var_dump()` en handlers.**

2. **Vistas Twig & UI:**
   - Mantener el sistema de diseño existente: Dark/Light mode con Alpine.js (`:class="{ 'w3-dark-mode': isDarkMode }"`).
   - Sidebar consistente: Todos los menús deben incluir los enlaces principales (*Dashboard, Clientes, Proveedores, Vendedores, Tasas de Cambio, Inventario, POS, Actualizar Contraseña, Cerrar Sesión*).
   - Para tablas con búsqueda en vivo, pasar los datos a Alpine mediante array serializado en JSON o Twig loop:
     ```twig
     suppliersList: [
       {% for supplier in suppliers %}
       { id: {{ supplier.id }}, name: '{{ supplier.name|e('js') }}', ... }{% if not loop.last %},{% endif %}
       {% endfor %}
     ]
     ```
   - Usar modales de confirmación antes de eliminar registros (vía `/modulo/{id}/eliminar`).

3. **Commits y Control de Versiones:**
   - Seguir Conventional Commits en inglés (ej: `feat(suppliers): implement CRUD...`, `fix(...)`, `refactor(...)`).
