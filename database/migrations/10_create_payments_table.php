<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();

        $db->createTableIfNotExists('payments', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'sale_id' => '
                INTEGER
                NOT NULL
                REFERENCES sales(id)
            ',
            'amount' => 'REAL NOT NULL CHECK (amount > 0)',
            'currency' => '
                TEXT
                NOT NULL
                CHECK (currency IN ("usd", "cop", "ves"))
            ',
            'exchange_rate_id' => '
                INTEGER
                NOT NULL
                REFERENCES exchange_rates(id)
            ',
        ]);

        $db->execute();
    }
};
