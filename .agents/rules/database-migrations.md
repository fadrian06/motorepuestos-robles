# Reglas para Migraciones y Base de Datos

Las migraciones residen en `database/migrations/` y definen la estructura del esquema relacional del sistema mediante `leafs/auth` y el contrato `App\Migration`.

## 1. Convención de Nomenclatura y Estructura

- Cada archivo debe seguir el orden numérico secuencial con prefijo de dos dígitos:
  - `01_create_roles_and_permissions_tables.php`
  - `02_create_users_table.php`
  - `03_create_clients_table.php`
  - `04_create_suppliers_table.php`
  - `05_create_products_table.php`
  - `06_create_purchases_table.php`
  - `07_create_purchases_items_table.php`
  - `08_create_sales_table.php`
  - `09_create_sales_items_table.php`
  - `10_create_payments_table.php`
  - `11_create_loses_table.php`
- El archivo debe retornar una clase anónima `readonly` que implemente `App\Migration`:
  ```php
  <?php

  declare(strict_types=1);

  use App\Migration;
  use Leaf\Auth;

  return new readonly class implements Migration
  {
      #[Override]
      public function up(Auth $auth): void
      {
          $db = $auth->db();

          $db->createTableIfNotExists('table_name', [
              'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
              'campo' => 'TEXT NOT NULL',
          ]);

          $db->execute();
      }
  };
  ```

## 2. Tipos de Datos y Restricciones (Compatibilidad SQLite / MySQL)

- Usar tipos compatibles entre SQLite y MySQL:
  - Llaves primarias: `'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT'`
  - Cadenas y texto: `'campo' => 'TEXT NOT NULL'` o `'campo' => 'TEXT NOT NULL UNIQUE'`
  - Números decimales / precios: `'price' => 'REAL NOT NULL CHECK (price > 0)'`
  - Números enteros / foráneas: `'client_id' => 'INTEGER NOT NULL'`
  - Fechas: `'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'`
- Llamar siempre a `$db->execute()` al final para aplicar la creación.
