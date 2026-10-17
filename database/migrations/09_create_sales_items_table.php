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
                'sale_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (sale_id)
                    REFERENCES sales(id)
                ',
                'purchase_item_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (purchase_item_id)
                    REFERENCES purchases_items(id)
                ',
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                'sale_id' => '
                    INTEGER
                    NOT NULL
                    REFERENCES sales(id)
                ',
                'purchase_item_id' => '
                    INTEGER
                    NOT NULL
                    REFERENCES purchases_items(id)
                ',
            ],
        };

        $db->createTableIfNotExists('sales_items', [
            'product_price' => 'REAL NOT NULL CHECK (product_price > 0)',
            'quantity' => 'REAL NOT NULL CHECK (quantity > 0)',
        ] + $keys);

        $db->execute();
    }
};
