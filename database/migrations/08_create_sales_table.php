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
                'client_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (client_id)
                    REFERENCES clients(id)
                ',
                'user_id' => "
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (user_id)
                    REFERENCES {$authTable}({$authIdKey})
                ",
                'exchange_rate_id' => '
                    INTEGER
                    NOT NULL,
                    FOREIGN KEY (exchange_rate_id)
                    REFERENCES exchange_rates(id)
                ',
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
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
            ],
        };

        $db->createTableIfNotExists('sales', [
            'code' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(code) > 0)',
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
