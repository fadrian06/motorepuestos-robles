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

        $db->createTableIfNotExists('products_losses', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'purchase_item_id' => '
                INTEGER
                NOT NULL
                REFERENCES purchases_items(id)
            ',
            'quantity' => '
                REAL
                NOT NULL
                CHECK (quantity > 0)
            ',
            'reason' => '
                TEXT
                NOT NULL
                CHECK (reason IN ("expired", "damaged", "taken"))
            ',
            'created_at' => '
                TEXT
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                CHECK (created_at LIKE "____-__-__ __:__:__")
            ',
            'user_id' => "
                INTEGER
                NOT NULL
                REFERENCES {$authTable}({$authIdKey})
            ",
        ]);

        $db->execute();
    }
};
