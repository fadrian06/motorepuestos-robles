# Reglas para Rutas, Middlewares y Seguridad

Todas las rutas web se definen en `routes/web.php` usando el enrutador regex del framework y pipelines de middleware PSR-15.

## 1. Definición de Rutas y Expresiones Regulares

- Las rutas usan expresiones regulares delimitadas con `#^...$#`.
- Para capturar identificadores numéricos, usar grupos con nombre:
  - Edición: `'#^/modulo/(?<id>\d+)$#'`
  - Eliminación: `'#^/modulo/(?<id>\d+)/eliminar$#'`
  - Verificación o acciones específicas: `'#^/modulo/(?<id>\d+)/verificar$#'`
- Métodos HTTP explícitos: `['GET']`, `['GET', 'POST']`.

## 2. Pipeline de Middlewares (`QueueRequestHandler`)

- Cada ruta se ejecuta a través de un `QueueRequestHandler` donde el primer argumento es el handler final y los siguientes son los middlewares que se ejecutan en orden:
  ```php
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
  ```

## 3. Permisos y Autorización

- Los permisos del sistema se centralizan en el enum `App\Enums\Permission`.
- Al agregar un nuevo módulo con acciones restringidas:
  1. Agregar los casos correspondientes en `App\Enums\Permission` (ej: `ViewSuppliers = 'view suppliers'`, `RegisterSupplier = 'register supplier'`, etc.).
  2. Proteger las rutas con `$authorize->withPermissions('permiso')`.
