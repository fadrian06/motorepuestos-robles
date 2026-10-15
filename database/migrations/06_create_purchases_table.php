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

        $db->createTableIfNotExists('purchases', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'user_id' => "INTEGER NOT NULL REFERENCES {$authTable}({$authIdKey}) ON DELETE CASCADE",
            'supplier_id' => 'INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE',
            'supplier_invoice_code' => 'TEXT NOT NULL',
            'created_at' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'verified_at' => 'TEXT',
        ]);

        $db->execute();
    }
};
