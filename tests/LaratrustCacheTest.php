<?php

namespace Laratrust\Test;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustCacheTest extends LaratrustTestCase
{
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->migrate();
    }

    public function testUserCanDisableTheRolesAndPermissionsCaching()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $role = Role::create(['name' => 'role_a'])
            ->attachPermissions([
                Permission::create(['name' => 'permission_a']),
                Permission::create(['name' => 'permission_b']),
                Permission::create(['name' => 'permission_c']),
            ]);

        $user->roles()->attach($role->id);

        $user->permissions()->attach([
            Permission::create(['name' => 'permission_d'])->id => ['team_id' => $team->id ],
            Permission::create(['name' => 'permission_e'])->id => ['team_id' => $team->id],
        ]);

        /*
        |------------------------------------------------------------
        | User Assertion
        |------------------------------------------------------------
        */
        // With cache
        $this->app['config']->set('laratrust.use_cache', true);
        $this->assertInternalType('array', $user->cachedRoles());
        $this->assertEquals($user->roles()->get()->toArray(), $user->cachedRoles());

        $this->assertInternalType('array', $user->cachedPermissions());
        $this->assertEquals($user->permissions()->get()->toArray(), $user->cachedPermissions());

        // Without cache
        $this->app['config']->set('laratrust.use_cache', false);
        $this->assertInstanceOf('Illuminate\Support\Collection', $user->cachedRoles());
        $this->assertEquals($user->roles()->get(), $user->cachedRoles());

        $this->assertInstanceOf('Illuminate\Support\Collection', $user->cachedPermissions());
        $this->assertEquals($user->permissions()->get(), $user->cachedPermissions());

        /*
        |------------------------------------------------------------
        | Role Assertion
        |------------------------------------------------------------
        */
        // With cache
        $this->app['config']->set('laratrust.use_cache', true);
        $this->assertInternalType('array', $role->cachedPermissions());
        $this->assertEquals($role->permissions()->get()->toArray(), $role->cachedPermissions());

        // Without cache
        $this->app['config']->set('laratrust.use_cache', false);
        $this->assertInstanceOf('Illuminate\Support\Collection', $role->cachedPermissions());
        $this->assertEquals($role->permissions()->get(), $role->cachedPermissions());
    }
}
