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

        $db->createTableIfNotExists('sales', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'code' => 'TEXT NOT NULL UNIQUE',
            'created_at' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'client_id' => 'INTEGER NOT NULL REFERENCES clients(id) ON DELETE CASCADE',
            'user_id' => "INTEGER NOT NULL REFERENCES {$authTable}({$authIdKey}) ON DELETE CASCADE",
        ]);

        $db->execute();
    }
};
