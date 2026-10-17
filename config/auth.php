<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Migration;
use Leaf\Auth;
use Leaf\Helpers\Password;

use function Faslatam\PsrFramework\getenv;

return static function (): Auth {
    $auth = new Auth();

    $auth->config(
        'password.encode',
        static fn(
            #[SensitiveParameter]
            string $password,
        ): string => (string) Password::hash($password, Password::BCRYPT, [
            'cost' => getenv('BCRYPT_ROUNDS'),
        ]),
    );

    $auth->config('session', true);
    $auth->config('token.secret', getenv('TOKEN_SECRET'));

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
    $db->select($authTable, 'email, permissions');

    /** @var array{email: string, permissions: string}[] */
    $users = $db->all();

    $customPermissions = [];

    foreach ($users as $user) {
        /** @var string[] */
        $permissions = json_decode($user['permissions'], associative: true);

        $customPermissions[$user['email']] = $permissions;
    }

    $auth->createRoles([
        'admin' => Permission::names(),
        'seller' => [],
    ] + $customPermissions);

    return $auth;
};
