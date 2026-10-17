<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();

        $keys = match ($_ENV['DB_CONNECTION']) {
            'mysql' => [
                'id' => 'INTEGER PRIMARY KEY AUTO_INCREMENT',
                'purchase_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (purchase_id)
                    REFERENCES purchases(id)
                ',
                'product_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (product_id)
                    REFERENCES products(id)
                ',
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                'purchase_id' => '
                    INTEGER
                    NOT NULL
                    REFERENCES purchases(id)
                ',
                'product_id' => '
                    INTEGER
                    NOT NULL
                    REFERENCES products(id)
                ',
            ],
        };

        $db->createTableIfNotExists('purchases_items', [
            'quantity' => '
                REAL
                NOT NULL
                CHECK (quantity > 0)
            ',
            'cost' => 'REAL NOT NULL CHECK (cost > 0)',
        ] + $keys);

        $db->execute();
    }
};
