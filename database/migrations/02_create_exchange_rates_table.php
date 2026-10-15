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

        $db->createTableIfNotExists('exchange_rates', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'created_at' => '
                TEXT
                NOT NULL
                UNIQUE
                DEFAULT CURRENT_TIMESTAMP
                CHECK (created_at LIKE "____-__-__ __:__:__")
            ',
            'usd_ves' => 'REAL NOT NULL CHECK (usd_ves > 0)',
            'usd_cop' => 'REAL NOT NULL CHECK (usd_cop > 0)',
        ]);

        $db->execute();
    }
};
