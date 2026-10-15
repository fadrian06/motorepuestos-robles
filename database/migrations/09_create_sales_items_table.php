<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void {
        $db = $auth->db();

        $db->createTableIfNotExists('sales_items', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'sale_id' => 'INTEGER NOT NULL REFERENCES sales(id) ON DELETE CASCADE',
            'purchase_item_id' => 'INTEGER NOT NULL REFERENCES purchases_items(id) ON DELETE CASCADE',
            'product_id' => 'INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE',
            'quantity' => 'REAL NOT NULL',
        ]);

        $db->execute();
    }
};
