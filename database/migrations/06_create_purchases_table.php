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

        $keys = match ($_ENV['DB_CONNECTION']) {
            'mysql' => [
                'id' => 'INTEGER PRIMARY KEY AUTO_INCREMENT',
                'user_id' => "
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (user_id)
                    REFERENCES {$authTable}({$authIdKey})
                ",
                'supplier_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (supplier_id)
                    REFERENCES suppliers(id)
                ',
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
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
            ],
        };

        $db->createTableIfNotExists('purchases', [
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
        ] + $keys);

        $db->execute();
    }
};
