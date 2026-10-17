<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();
        $authTable = (string) $auth->config('db.table');
        $authIdKey = (string) $auth->config('id.key');

        $autoincrement = match ($_ENV['DB_CONNECTION']) {
            'mysql' => 'AUTO_INCREMENT',
            default => 'AUTOINCREMENT',
        };

        $db->createTableIfNotExists('purchases', [
            'id' => "INTEGER PRIMARY KEY {$autoincrement}",
            'user_id' => "
                INTEGER
                NOT NULL
                REFERENCES {$authTable}({$authIdKey})
            ",
            'supplier_id' => '
                INTEGER
                NOT NULL
                REFERENCES suppliers(id)
            ',
            'supplier_invoice_code' => '
                TEXT
                NOT NULL
                UNIQUE
                CHECK (LENGTH(supplier_invoice_code) > 0)
            ',
            'created_at' => '
                TEXT
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                CHECK (created_at LIKE "____-__-__ __:__:__")
            ',
            'verified_at' => '
                TEXT
                UNIQUE
                CHECK (
                    verified_at IS NULL
                    OR (
                        verified_at LIKE "____-__-__ __:__:__"
                        AND verified_at >= created_at
                    )
                )
            ',
        ]);

        $db->execute();
    }
};
