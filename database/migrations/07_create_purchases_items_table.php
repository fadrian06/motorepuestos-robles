<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();

        $autoincrement = match ($_ENV['DB_CONNECTION']) {
            'mysql' => 'AUTO_INCREMENT',
            default => 'AUTOINCREMENT',
        };

        $db->createTableIfNotExists('purchases_items', [
            'id' => "INTEGER PRIMARY KEY {$autoincrement}",
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
            'quantity' => '
                REAL
                NOT NULL
                CHECK (quantity > 0)
            ',
            'cost' => 'REAL NOT NULL CHECK (cost > 0)',
        ]);

        $db->execute();
    }
};
