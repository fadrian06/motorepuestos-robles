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

Para garantizar la compatibilidad entre SQLite y MySQL, se debe utilizar un patrón de definición basado en la conexión activa (`$_ENV['DB_CONNECTION']`).

### Llaves Primarias y Autoincremento
El autoincremento varía significativamente entre motores. Definir la PK basándose en la conexión:
```php
$pk = match($_ENV['DB_CONNECTION']) {
    'mysql' => 'INT AUTO_INCREMENT PRIMARY KEY',
    default => 'INTEGER PRIMARY KEY AUTOINCREMENT',
};
```

### Llaves Foráneas y Restricciones
MySQL requiere sintaxis explícita para las llaves foráneas fuera de la definición de columnas, mientras que SQLite prefiere referencias en línea.

1. **Definición de columnas:** Usar tipos compatibles (`TEXT`, `REAL`, `INTEGER`).
2. **Definición de llaves:** Consolidar todas las llaves (primarias y foráneas) en una variable `$keys` y aplicarlas mediante `$db->execute()` utilizando el patrón de compatibilidad:
```php
$keys = match($_ENV['DB_CONNECTION']) {
    'mysql' => [
        "PRIMARY KEY ($pk_col)",
        "CONSTRAINT fk_name FOREIGN KEY (col) REFERENCES table(id) ON DELETE CASCADE",
    ],
    default => [
        "FOREIGN KEY (col) REFERENCES table(id) ON DELETE CASCADE",
    ],
};
```

### Tipos Recomendados
- Cadenas y texto: `'TEXT NOT NULL'` o `'TEXT NOT NULL UNIQUE'`
- Números decimales / precios: `'REAL NOT NULL CHECK (price > 0)'`
- Fechas: `'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP'`

