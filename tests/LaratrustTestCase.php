<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Laratrust\LaratrustFacade;
use Orchestra\Testbench\TestCase;

class LaratrustTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Laratrust\LaratrustServiceProvider::class];
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
        $app['config']->set('laratrust.user_models.users', 'Laratrust\Tests\Models\User');
        $app['config']->set('laratrust.panel.register', true);
        $app['config']->set('laratrust.models', [
            'role' => 'Laratrust\Tests\Models\Role',
            'permission' => 'Laratrust\Tests\Models\Permission',
            'group' => 'Laratrust\Tests\Models\Group',
        ]);
    }

    public function migrate()
    {
        $migrations = [
            \Laratrust\Tests\Migrations\UsersMigration::class,
            \Laratrust\Tests\Migrations\LaratrustSetupTables::class,
        ];

        foreach ($migrations as $migration) {
            (new $migration)->up();
        }
    }
}
