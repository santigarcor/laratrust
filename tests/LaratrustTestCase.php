<?php

declare(strict_types=1);

namespace Laratrust\Tests;

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
        $app['config']->set('database.connections.testing', [
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
