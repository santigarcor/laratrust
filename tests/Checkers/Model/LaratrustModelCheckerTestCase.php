<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\Model;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\Other;
use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustModelCheckerTestCase extends LaratrustTestCase
{
    protected $user;

    protected $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $this->other = Other::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.use_morph_map', true);
        $this->app['config']->set('laratrust.user_models', [
            'users' => 'Laratrust\Tests\Models\User',
            'others' => 'Laratrust\Tests\Models\Other'
        ]);
    }

    public function modelDisableTheRolesAndPermissionsCachingAssertions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $role = Role::create(['name' => 'role_a'])
            ->givePermissions([
                Permission::create(['name' => 'permission_a']),
                Permission::create(['name' => 'permission_b']),
                Permission::create(['name' => 'permission_c']),
            ]);

        $this->user->roles()->attach($role->id);
        $this->user->permissions()->attach([
            Permission::create(['name' => 'permission_d']),
            Permission::create(['name' => 'permission_e']),
        ]);

        $this->other->roles()->attach($role->id);
        $this->other->permissions()->attach([
            Permission::UpdateOrcreate(['name' => 'permission_d']),
            Permission::UpdateOrcreate(['name' => 'permission_e']),
        ]);

        // With cache
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');

        $this->other->hasRole('some_role');
        $this->other->hasPermission('some_permission');

        $this->assertTrue(Cache::has("laratrust_cache_for_{$this->user->id}"));
        $this->assertTrue(Cache::has("laratrust_cache_for_{$this->other->id}"));

        $this->user->flushCache(false);
        $this->other->flushCache(false);

        // Without cache
        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');

        $this->other->hasRole('some_role');
        $this->other->hasPermission('some_permission');

        $this->assertFalse(Cache::has("laratrust_cache_for_{$this->user->id}"));
        $this->assertFalse(Cache::has("laratrust_cache_for_{$this->other->id}"));
    }

    public function migrate()
    {
        $migrations = [
            \Laratrust\Tests\Migrations\UsersMigration::class,
            \Laratrust\Tests\Migrations\OthersMigration::class,
            \Laratrust\Tests\Migrations\LaratrustSetupTables::class,
        ];

        foreach ($migrations as $migration) {
            (new $migration)->up();
        }
    }
}
