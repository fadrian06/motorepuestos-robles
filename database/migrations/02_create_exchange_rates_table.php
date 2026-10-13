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
            'created_at' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'usd_ves' => 'REAL NOT NULL',
            'usd_cop' => 'REAL NOT NULL',
        ]);

        $db->execute();
    }
};
