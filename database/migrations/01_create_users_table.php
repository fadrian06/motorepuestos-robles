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

        /** @var string */
        $authTable = $auth->config('db.table');

        /** @var string */
        $authIdKey = $auth->config('id.key');

        /** @var string */
        $rolesKey = $auth->config('roles.key');

        /** @var string */
        $passwordKey = $auth->config('password.key');

        $db->createTableIfNotExists($authTable, [
            $authIdKey => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'email' => 'TEXT NOT NULL UNIQUE CHECK (email LIKE "%@%")',
            $passwordKey => "
                TEXT
                NOT NULL
                CHECK (LENGTH({$passwordKey}) > 0)
            ",
            $rolesKey => "TEXT NOT NULL CHECK ({$rolesKey} LIKE '[\"%\"]')",
            'permissions' => 'TEXT NOT NULL CHECK (permissions LIKE "[%]")',
        ]);

        $db->execute();
    }
};
