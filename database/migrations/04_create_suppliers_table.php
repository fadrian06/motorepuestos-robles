<?php

declare(strict_types=1);

use App\Migration;
use Leaf\Auth;

return new readonly class implements Migration {
    #[Override]
    public function up(Auth $auth): void
    {
        $db = $auth->db();

        $keys = match ($_ENV['DB_CONNECTION']) {
            'mysql' => [
                'id' => 'INTEGER PRIMARY KEY AUTO_INCREMENT',
            ],
            default => [
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            ],
        };

        $db->createTableIfNotExists('suppliers', [
            'name' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(name) > 0)',
            'tax_id' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(tax_id) > 0)',
            'address' => 'TEXT NOT NULL CHECK (LENGTH(address) > 0)',
            'phone' => 'TEXT NOT NULL UNIQUE CHECK (phone LIKE "+% %-%")',
            'seller' => 'TEXT NOT NULL CHECK (LENGTH(seller) > 0)',
            'alias' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(alias) > 0)',
        ] + $keys);

        $db->execute();
    }
};
