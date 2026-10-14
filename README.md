![Logo de MOTOREPUESTOS Y MECÁNICA ROBLES](resources/images/logo.jpeg)

[![Autor](https://img.shields.io/badge/autor-Franyer_Sánchez-blue)](https://github.com/fadrian06)
[![Licencia](https://img.shields.io/badge/licencia-propietaria-red)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777bb4)](https://php.net)
[![Sitio Web](https://img.shields.io/badge/web-InfinityFree-22c55e)](https://motorepuestos-robles.infinityfreeapp.com)

> Desarrollado por **Franyer Sánchez** ([@fadrian06](https://github.com/fadrian06))
> para **MOTOREPUESTOS Y MECÁNICA ROBLES**.

Sistema web de gestión comercial y venta de repuestos automotrices con control de inventario por lote y trazabilidad de compras a clientes.

---

## Características Principales

- **Gestión de Proveedores (CRUD Completo)** — Directorio de importadoras y distribuidores mayoristas con RIF, alias para lotes, asesor comercial asignado, teléfono y dirección física.
- **Directorio de Clientes** — Registro y administración de compradores particulares y talleres mecánicos para cotizaciones y ventas.
- **Gestión de Vendedores y Permisos Granulares** — Control de acceso basado en permisos (`App\Enums\Permission`) con gestión de roles y restablecimiento de accesos.
- **Cotizaciones y Tasas de Cambio** — Registro y actualización en tiempo real de tasas oficiales USD/VES y USD/COP para cálculo y preservación histórica en ventas.
- **Seguridad y Autenticación Robusta** — Registro, inicio de sesión, verificación de correo electrónico vía token, recuperación y cambio de contraseñas.
- **Interfaz Moderna y Modo Oscuro** — UI responsiva optimizada con W3.CSS, variables de diseño de marca (`--brand-red`), Alpine.js para búsquedas reactivas en vivo, modales y alternador de tema claro/oscuro.
- **Próximas Fases:**
  - *Inventario y Lotes* — Registro de compras con costo unitario y stock calculado por suma de lotes disponibles.
  - *Punto de Venta (POS)* — Búsqueda por código/nombre, selección manual de lote por el vendedor, comprobante imprimible y pago mixto.

---

## Requisitos del Sistema

- **PHP 8.4 o superior** (`declare(strict_types=1);`, constructor promotion readonly, atributos `#[Override]` y `#[NoDiscard]`).
- **Extensiones de PHP:**
  - `pdo_sqlite` (desarrollo local / pruebas)
  - `pdo_mysql` (producción)
  - `mbstring`
  - `openssl`
  - `json`
- **Composer 2+**
- **Servidor Web:** Apache 2.4 con módulo `mod_rewrite` activo (o PHP Built-in Server para desarrollo).

---

## Instalación y Puesta en Marcha

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/fadrian06/motorepuestos-robles.git
   cd motorepuestos-robles
   ```

2. **Instalar dependencias de Composer:**
   ```bash
   composer install
   ```

3. **Configurar el entorno:**
   ```bash
   cp .env.example .env
   ```
   Ajustar los parámetros de base de datos (`DB_CONNECTION=sqlite` o `mysql`) y configuración de correo SMTP en el archivo `.env`.

4. **Ejecutar el servidor local:**
   ```bash
   composer serve
   ```
   *El servidor quedará disponible en `http://localhost:80` (o el puerto configurado).*

---

## Scripts y Herramientas de Desarrollo

El proyecto integra herramientas de análisis estático, formato y pruebas automatizadas:

```bash
# Ejecutar linter y análisis estático con Carthage Mago
composer lint

# Formatear el código fuente según los estándares del proyecto
composer format

# Ejecutar la suite de pruebas automatizadas con PHPUnit
composer test

# Iniciar servidor de desarrollo local
composer serve
```

---

## Estructura del Proyecto

```text
motorepuestos-robles/
├── app/
│   ├── Enums/
│   │   └── Permission.php             # Enums de permisos del sistema (spatie-like)
│   ├── Http/
│   │   ├── Handlers/                  # Handlers PSR-15 individuales por acción (Single Responsibility)
│   │   └── Middlewares/               # Middlewares (Authenticate, Authorize, VerifyEmail, etc.)
│   └── Migration.php                  # Contrato base para migraciones de BD
├── database/
│   ├── database.sqlite                # Base de datos local SQLite (ignorado en git)
│   └── migrations/                    # Migraciones numeradas del sistema
├── resources/
│   ├── images/                        # Logotipos, favicons y banners
│   ├── scripts/                       # Alpine.js y librerías cliente
│   ├── styles/                        # W3.CSS con personalización de marca
│   └── views/
│       ├── emails/                    # Plantillas de correo (tokens de verificación y clave)
│       └── pages/                     # Vistas Twig del panel y formularios
├── routes/
│   └── web.php                        # Definición central de rutas y pipelines de middlewares
├── index.php                          # Punto de entrada HTTP único
├── composer.json                      # Dependencias y scripts de automatización
└── AGENTS.md                          # Guía de contexto y arquitectura para agentes AI
```

---

## Módulos y Rutas del Sistema

| Módulo | Rutas | Descripción |
| :--- | :--- | :--- |
| **Dashboard** | `GET /` | Panel principal de métricas y accesos rápidos de operaciones. |
| **Proveedores** | `GET /proveedores`<br>`GET,POST /proveedores/registrar`<br>`GET,POST /proveedores/{id}`<br>`GET /proveedores/{id}/eliminar` | Directorio, alta, modificación y eliminación con confirmación modal. |
| **Clientes** | `GET /clientes`<br>`GET,POST /clientes/registrar`<br>`GET,POST /clientes/{id}`<br>`GET /clientes/{id}/eliminar` | Cartera de compradores y talleres automotrices. |
| **Vendedores** | `GET /vendedores`<br>`GET,POST /vendedores/registrar` | Equipo comercial y asignación de permisos de usuario. |
| **Cotizaciones** | `GET,POST /cotizaciones` | Ajuste de tasas de cambio operativas USD / VES / COP. |
| **Autenticación** | `GET,POST /entrar`<br>`GET /salir`<br>`GET,POST /registrarse`<br>`GET,POST /verificar-correo`<br>`GET,POST /recuperar-clave`<br>`GET,POST /restablecer-clave`<br>`GET,POST /actualizar-clave` | Flujo de seguridad, verificación de correo y contraseñas. |

---

## Esquema de Base de Datos

### Tablas Implementadas:
- **`users`** — Cuentas de usuario con roles, permisos serializados, correo único y estado de verificación.
- **`exchange_rates`** — Historial y registro de tasas de cambio (`usd_ves`, `usd_cop`).
- **`clients`** — Cartera de clientes (`id`, `name`).
- **`suppliers`** — Proveedores con `name`, `tax_id` (RIF), `alias`, `seller`, `phone` y `address`.

### Tablas Planificadas (Inventario y Ventas):
- `products` — Catálogo de repuestos con precio de venta base.
- `purchases` / `purchase_items` — Cabecera de compra y renglones de lote con costo unitario y cantidad disponible.
- `sales` / `sale_items` — Cabecera de venta y detalle enlazado al lote específico consumido.
- `payments` — Métodos y montos aplicados a cada venta (pago mixto).

---

## Despliegue en Producción

El sistema está diseñado para ser compatible tanto con servidores dedicados como con entornos de hosting compartido (e.g. InfinityFree / cPanel con Apache + PHP 8.4+ + MySQL).

- Verificar que el módulo `mod_rewrite` esté activo.
- Asegurar que el archivo `.htaccess` en la raíz restrinja el acceso directo a los directorios protegidos (`app/`, `database/`, `resources/views/`, `routes/`, `vendor/`) y enrute todo el tráfico a través de `index.php`.

---

## Autor y Licencia

| | |
|---|---|
| **Desarrollador** | Franyer Sánchez |
| **GitHub** | [@fadrian06](https://github.com/fadrian06) |
| **Correo** | franyeradriansanchez@gmail.com |
| **Cliente** | MOTOREPUESTOS Y MECÁNICA ROBLES |
| **Año** | 2026 |

Ver el archivo [`LICENSE`](LICENSE) para más detalles sobre los términos de uso y derechos de autor.
