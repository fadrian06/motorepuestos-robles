<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();

        $autoincrement = match ($_ENV['DB_CONNECTION']) {
            'mysql' => 'AUTO_INCREMENT',
            default => 'AUTOINCREMENT',
        };

        $db->createTableIfNotExists('clients', [
            'id' => "INTEGER PRIMARY KEY {$autoincrement}",
            'name' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(name) > 0)',
        ]);

        $db->execute();
    }
};
