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
            'code' => 'TEXT NOT NULL UNIQUE',
            'name' => 'TEXT NOT NULL UNIQUE',
            'category' => 'TEXT NOT NULL',
            'price' => 'REAL NOT NULL',
        ]);

        $db->execute();
    }
};
