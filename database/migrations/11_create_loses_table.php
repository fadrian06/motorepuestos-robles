<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

use function Faslatam\PsrFramework\getenv;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();
        $authTable = (string) $auth->config('db.table');
        $authIdKey = (string) $auth->config('id.key');

        $keys = match (getenv('DB_CONNECTION')) {
            'mysql' => [
                'id' => 'INTEGER PRIMARY KEY AUTO_INCREMENT',
                'purchase_item_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (purchase_item_id)
                    REFERENCES purchases_items(id)
                ',
                'user_id' => "
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (user_id)
                    REFERENCES {$authTable}({$authIdKey})
                ",
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                'purchase_item_id' => '
                    INTEGER
                    NOT NULL
                    REFERENCES purchases_items(id)
                ',
                'user_id' => "
                    INTEGER
                    NOT NULL
                    REFERENCES {$authTable}({$authIdKey})
                ",
            ],
        };

        $db->createTableIfNotExists('products_losses', [
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
        ] + $keys);

        $db->execute();
    }
};
