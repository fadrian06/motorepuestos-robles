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

        $autoincrement = match ($_ENV['DB_CONNECTION']) {
            'mysql' => 'AUTO_INCREMENT',
            default => 'AUTOINCREMENT',
        };

        $db->createTableIfNotExists('sales', [
            'id' => "INTEGER PRIMARY KEY {$autoincrement}",
            'code' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(code) > 0)',
            'created_at' => '
                TEXT
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                CHECK (created_at LIKE "____-__-__ __:__:__")
            ',
            'client_id' => '
                INTEGER
                NOT NULL
                REFERENCES clients(id)
            ',
            'user_id' => "
                INTEGER
                NOT NULL
                REFERENCES {$authTable}({$authIdKey})
            ",
            'exchange_rate_id' => '
                INTEGER
                NOT NULL
                REFERENCES exchange_rates(id)
            ',
        ]);

        $db->execute();
    }
};
