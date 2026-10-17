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

        $keys = match (getenv('DB_CONNECTION')) {
            'mysql' => [
                'id' => 'INTEGER PRIMARY KEY AUTO_INCREMENT',
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            ],
        };

        $db->createTableIfNotExists('exchange_rates', [
            'created_at' => '
                TEXT
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                CHECK (created_at LIKE "____-__-__ __:__:__")
            ',
            'usd_ves' => 'REAL NOT NULL CHECK (usd_ves > 0)',
            'usd_cop' => 'REAL NOT NULL CHECK (usd_cop > 0)',
        ] + $keys);

        $db->execute();
    }
};
