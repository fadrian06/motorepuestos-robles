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

        $db->createTableIfNotExists('clients', [
            'name' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(name) > 0)',
        ] + $keys);

        $db->execute();
    }
};
