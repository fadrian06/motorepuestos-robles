# MOTOREPUESTOS Y MECÁNICA ROBLES

[![Autor](https://img.shields.io/badge/autor-Franyer_Sánchez-blue)](https://github.com/fadrian06)
[![Licencia](https://img.shields.io/badge/licencia-propietaria-red)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3-777bb4)](https://php.net)

> Desarrollado por **Franyer Sánchez** ([@fadrian06](https://github.com/fadrian06))
> para MOTOREPUESTOS Y MECÁNICA ROBLES.

Sistema de registro y control de ventas para un comercio de productos
automotrices. Permite gestionar el catálogo de productos, las compras a
proveedores, los clientes y las ventas, con trazabilidad completa entre el
lote comprado y la venta que lo consumió.

## Características

- **Catálogo de productos** — alta, edición y baja de productos, agrupados
  por categoría y con precio de venta único.
- **Compras por lote** — cada renglón de una compra a proveedor se registra
  como un lote independiente, con su propio costo unitario y cantidad
  disponible.
- **Inventario por lote** — el stock de un producto es la suma de las
  cantidades disponibles de sus lotes. Cada venta descuenta del lote
  seleccionado manualmente por el vendedor.
- **Punto de venta** — búsqueda rápida por código, nombre o categoría,
  selección de lote, carrito, cobro en uno o varios métodos de pago y
  comprobante imprimible.
- **Trazabilidad** — cada línea de venta queda asociada al lote del que
  salió la mercancía, lo que permite conocer el proveedor, la fecha y el
  costo de origen de cada repuesto vendido.
- **Cotización configurable** — la tasa de cambio se guarda junto a cada
  venta para conservar el valor histórico.
- **Clientes** — registro de nombre del comprador, con opción de
  "Consumidor final".
- **Proveedores** — datos comerciales completos, incluyendo RIF, vendedor
  asignado y alias.
- **Usuarios y permisos** — autenticación por correo electrónico verificado,
  recuperación de contraseña por enlace temporal y permisos granulares por
  usuario.
- **Comprobante de venta** — formato simple, imprimible desde el navegador.

## Fuera de alcance

Este sistema **no incluye** y no está previsto que incluya:

- Módulo de servicios de mecánica o mano de obra.
- Órdenes de trabajo por vehículo.
- Alertas de stock mínimo.
- Reportes de utilidad, costo o margen.
- Kardex o histórico de movimientos de inventario.
- Anulación de ventas.

El único alcance cubierto es el ciclo **compra → inventario por lote → venta**
junto con la administración básica de usuarios, clientes y proveedores.

## Requisitos

- PHP 8.3 o superior
- Extensiones: `pdo`, `json`, `openssl` (para tokens), `mbstring`
- Composer
- MySQL 5.7+ (producción) o SQLite 3 (desarrollo)
- Apache 2.4 con `mod_rewrite` habilitado

## Instalación

1. Clonar el repositorio:
```cmd
git clone <url-del-repo> motorepuestos-mecanica-robles
cd motorepuestos-mecanica-robles
```

2. Instalar dependencias:
```cmd
composer install
```

3. Copiar el archivo de entorno y completar credenciales:
```cmd
cp .env.example .env
```

4. Crear la base de datos MySQL (o dejar que SQLite se cree solo en
desarrollo) y ajustar las variables `DB_*` en `.env`.

5. Crear el primer usuario administrador (ver sección *Primer usuario*).

6. Levantar el servidor (Apache apuntando a la raíz del proyecto, o
`php -S localhost:8000` con el `.htaccess` adaptado).

## Primer usuario

El primer usuario debe crearse con `verificado = 1` y el permiso comodín
`*` en la columna `permisos` de la tabla `usuarios`. Los usuarios
posteriores se registran desde `/register` y activan su cuenta mediante el
enlace de verificación enviado por correo.

## Estructura del proyecto
```cmd
motorepuestos-mecanica-robles/
├── index.php Punto de entrada único
├── composer.json
├── .env Variables de entorno (no versionado)
├── .env.example Plantilla de variables
├── .htaccess Reglas de reescritura y bloqueo
├── bootstrap/ Arranque de la aplicación
├── routes/ Definición de rutas HTTP
├── app/
│ ├── Http/ Middleware y handlers
│ ├── Models/ Acceso a datos
├── resources/
│ └── views/ Plantillas
├── database/
│ ├── database.sqlite Base de datos local (no versionado)
│ └── migrations/ Esquema SQL
└── vendor/ Dependencias de Composer
```


## Variables de entorno

Las variables principales se documentan en `.env.example`. Las más
relevantes:

| Variable | Descripción |
|---|---|
| `DB_CONNECTION` | `mysql` o `sqlite` |
| `DB_*` | Credenciales de conexión |

## Esquema de datos

Tablas principales:

- `usuarios` — cuentas del sistema con permisos
- `clientes` — compradores
- `productos` — catálogo de productos
- `proveedores` — datos de proveedores
- `compras` — cabecera de la compra
- `compra_detalle` — lotes: producto, cantidad, costo unitario, cantidad
  disponible, precio de venta del lote
- `ventas` — cabecera de la venta
- `venta_detalle` — líneas asociadas a un lote específico
- `pagos` — pagos aplicados a una venta (permite pago mixto)

## Uso

1. Ingresar con un usuario verificado.
2. Registrar proveedores en `/proveedores`.
3. Cargar el catálogo de productos en `/productos`.
4. Registrar compras en `/compras`; cada renglón crea un lote.
5. Vender desde `/ventas/vender`: buscar producto, elegir el lote del que se
   descuenta, agregar al carrito y cobrar.
6. Consultar ventas históricas en `/ventas`.

## Despliegue

Compatible con hosting compartido tipo InfinityFree (Apache + PHP 8.3 +
MySQL). Verificar que `mod_rewrite` esté activo y que el `.htaccess`
bloquee el acceso directo a `app/`, `bootstrap/`, `routes/`, `resources/`,
`database/` y `vendor/`.

## Autor

| | |
|---|---|
| **Desarrollador** | Franyer Sánchez |
| **GitHub** | [@fadrian06](https://github.com/fadrian06) |
| **Correo** | franyeradriansanchez@gmail.com |
| **Cliente** | MOTOREPUESTOS Y MECÁNICA ROBLES |
| **Año** | 2026 |

## Licencia

Ver el archivo [`LICENSE`](LICENSE) en la raíz del proyecto.

```LICENSE
Copyright (c) 2026 Franyer Sánchez. Desarrollado por encargo para
MOTOREPUESTOS Y MECÁNICA ROBLES. Todos los derechos reservados.
```
