<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Support\Facades\Schema;
use Laratrust\LaratrustFacade;
use Laratrust\LaratrustServiceProvider;
use Laratrust\Tests\Migrations\LaratrustSetupTables;
use Laratrust\Tests\Migrations\UsersMigration;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Orchestra\Testbench\TestCase;

class LaratrustTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaratrustServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['Laratrust' => LaratrustFacade::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        // CI also runs the tests on MySQL, with DB_CONNECTION=mysql and the other DB_* variables.
        $app['config']->set('database.connections.testing', env('DB_CONNECTION') === 'mysql' ? [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laratrust'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ] : [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('laratrust.user_models.users', User::class);
        $app['config']->set('laratrust.panel.register', true);
        $app['config']->set('laratrust.models', [
            'role' => Role::class,
            'permission' => Permission::class,
            'team' => Team::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A MySQL database keeps the tables of the previous test.
        Schema::dropAllTables();
    }

    public function migrate(): void
    {
        $migrations = [
            UsersMigration::class,
            LaratrustSetupTables::class,
        ];

        foreach ($migrations as $migration) {
            (new $migration)->up();
        }
    }
}
