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

        $db->createTableIfNotExists('purchases_items', [
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
