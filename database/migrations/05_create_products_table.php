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

        $db->createTableIfNotExists('products', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'code' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(code) > 0)',
            'name' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(name) > 0)',
            'category' => 'TEXT NOT NULL CHECK (LENGTH(category) > 0)',
            'price' => 'REAL NOT NULL CHECK (price > 0)',
        ]);

        $db->execute();
    }
};
