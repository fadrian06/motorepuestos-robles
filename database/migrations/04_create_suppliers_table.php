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

        $db->createTableIfNotExists('suppliers', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'name' => 'TEXT NOT NULL UNIQUE',
            'tax_id' => 'TEXT NOT NULL UNIQUE',
            'address' => 'TEXT NOT NULL',
            'phone' => 'TEXT NOT NULL',
            'seller' => 'TEXT NOT NULL',
            'alias' => 'TEXT NOT NULL',
        ]);

        $db->execute();
    }
};
