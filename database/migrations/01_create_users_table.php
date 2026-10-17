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
        $rolesKey = (string) $auth->config('roles.key');
        $passwordKey = (string) $auth->config('password.key');

        $keys = match (getenv('DB_CONNECTION')) {
            'mysql' => [
                $authIdKey => 'INTEGER PRIMARY KEY AUTO_INCREMENT',
            ],
            default => [
                $authIdKey => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            ],
        };

        $db->createTableIfNotExists($authTable, [
            'email' => 'TEXT NOT NULL UNIQUE CHECK (email LIKE "%@%")',
            $passwordKey => "
                TEXT
                NOT NULL
                CHECK (LENGTH({$passwordKey}) > 0)
            ",
            $rolesKey => "TEXT NOT NULL CHECK ({$rolesKey} LIKE '[\"%\"]')",
            'permissions' => 'TEXT NOT NULL CHECK (permissions LIKE "[%]")',
            'created_at' => '
                TEXT
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                CHECK (created_at LIKE "____-__-__ __:__:__")
            ',
            'updated_at' => '
                TEXT
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                CHECK (
                    updated_at LIKE "____-__-__ __:__:__"
                    AND updated_at >= created_at
                )
            ',
        ] + $keys);

        $db->execute();
    }
};
