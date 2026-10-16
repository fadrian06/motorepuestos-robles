<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();

        $db->createTableIfNotExists('sales_items', [
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
            'product_price' => 'REAL NOT NULL CHECK (product_price > 0)',
            'quantity' => 'REAL NOT NULL CHECK (quantity > 0)',
        ]);

        $db->execute();
    }
};
