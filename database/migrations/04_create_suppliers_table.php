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
            'name' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(name) > 0)',
            'tax_id' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(tax_id) > 0)',
            'address' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(address) > 0)',
            'phone' => 'TEXT NOT NULL UNIQUE CHECK (phone LIKE "+% %-%")',
            'seller' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(seller) > 0)',
            'alias' => 'TEXT NOT NULL UNIQUE CHECK (LENGTH(alias) > 0)',
        ]);

        $db->execute();
    }
};
