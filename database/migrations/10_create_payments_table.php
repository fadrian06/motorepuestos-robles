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
                'exchange_rate_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (exchange_rate_id)
                    REFERENCES exchange_rates(id)
                ',
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                'sale_id' => '
                    INTEGER
                    NOT NULL
                    REFERENCES sales(id)
                ',
                'exchange_rate_id' => '
                    INTEGER
                    NOT NULL
                    REFERENCES exchange_rates(id)
                ',
            ],
        };

        $db->createTableIfNotExists('payments', [
            'amount' => 'REAL NOT NULL CHECK (amount > 0)',
            'currency' => '
                TEXT
                NOT NULL
                CHECK (currency IN ("usd", "cop", "ves"))
            ',
        ] + $keys);

        $db->execute();
    }
};
