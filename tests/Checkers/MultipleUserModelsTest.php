<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers;

use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Migrations\LaratrustSetupTables;
use Laratrust\Tests\Migrations\OthersMigration;
use Laratrust\Tests\Migrations\UsersMigration;
use Laratrust\Tests\Models\Other;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;

class MultipleUserModelsTest extends LaratrustTestCase
{
    protected $user;

    protected $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();

        $this->app['config']->set('laratrust.use_morph_map', true);
        $this->app['config']->set('laratrust.user_models', [
            'users'  => User::class,
            'others' => Other::class,
        ]);

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $this->other = Other::create(['name' => 'test', 'email' => 'test@test.com']);
    }

    public function test_model_disable_the_roles_and_permissions_caching(): void
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

        $this->assertTrue(Cache::has("laratrust_user_users_{$this->user->id}"));

        $this->assertTrue(Cache::has("laratrust_user_others_{$this->other->id}"));

        $this->user->flushLaratrustCache();
        $this->other->flushLaratrustCache();

        // Without cache
        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');

        $this->other->hasRole('some_role');
        $this->other->hasPermission('some_permission');

        $this->assertFalse(Cache::has("laratrust_user_users_{$this->user->id}"));

        $this->assertFalse(Cache::has("laratrust_user_others_{$this->other->id}"));
    }

    public function migrate(): void
    {
        $migrations = [
            UsersMigration::class,
            OthersMigration::class,
            LaratrustSetupTables::class,
        ];

        foreach ($migrations as $migration) {
            (new $migration())->up();
        }
    }
}
