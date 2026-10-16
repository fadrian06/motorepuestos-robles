<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Migration;
use Leaf\Auth;
use Leaf\Helpers\Password;

return static function (): Auth {
    $auth = new Auth();

    $auth->config(
        'password.encode',
        static fn(
            #[SensitiveParameter]
            string $password,
        ): string => (string) Password::hash($password, Password::BCRYPT, [
            'cost' => $_ENV['BCRYPT_ROUNDS'],
        ]),
    );

    $auth->config('session', true);
    $auth->config('token.secret', $_ENV['TOKEN_SECRET']);

    $auth->autoConnect();

    $migrationFiles = (array) glob(__DIR__ . '/../database/migrations/*.php');

    foreach (array_filter($migrationFiles) as $migration) {
        $migration = (object) require $migration;

        if ($migration instanceof Migration) {
            $migration->up($auth);
        }
    }

    $authTable = (string) $auth->config('db.table');
    $db = $auth->db();
    $db->select($authTable);

    /** @var array[] */
    $users = $db->all();
    $customPermissions = [];

    foreach ($users as $user) {
        /** @var string */
        $permissions = $user['permissions'];

        $customPermissions[$user['email']] = json_decode(
            $permissions,
            associative: true,
        );
    }

    /** @var array<string, string[]> */
    $roles = [
        'admin' => Permission::keys(),
        'seller' => [],
        ...$customPermissions,
    ];

    $auth->createRoles($roles);

    return $auth;
};
