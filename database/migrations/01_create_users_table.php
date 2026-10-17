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
        $rolesKey = (string) $auth->config('roles.key');
        $passwordKey = (string) $auth->config('password.key');

        $autoincrement = match ($_ENV['DB_CONNECTION']) {
            'mysql' => 'AUTO_INCREMENT',
            default => 'AUTOINCREMENT',
        };

        $db->createTableIfNotExists($authTable, [
            $authIdKey => "INTEGER PRIMARY KEY {$autoincrement}",
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
        ]);

        $db->execute();
    }
};
