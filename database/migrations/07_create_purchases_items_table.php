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
            'purchase_id' => 'INTEGER NOT NULL REFERENCES purchases(id) ON DELETE CASCADE',
            'product_id' => 'INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE',
            'quantity_purchased' => 'REAL NOT NULL',
            'quantity_available' => 'REAL NOT NULL',
            'price' => 'REAL NOT NULL',
        ]);

        $db->execute();
    }
};
