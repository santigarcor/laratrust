<?php

namespace Laratrust\Test\Checkers\Role;

use Laratrust\Tests\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustRoleDefaultCheckerCacheTest extends LaratrustTestCase
{
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
        $role = Role::create(['name' => 'role_a'])
            ->attachPermissions([
                Permission::create(['name' => 'permission_a']),
                Permission::create(['name' => 'permission_b']),
                Permission::create(['name' => 'permission_c']),
            ]);

        // With cache
        $this->app['config']->set('laratrust.use_cache', true);
        $role->hasPermission('some_permission');
        $this->assertTrue(Cache::has("laratrust_permissions_for_role_{$role->id}"));
        $role->flushCache();

        // Without cache
        $this->app['config']->set('laratrust.use_cache', false);
        $role->hasPermission('some_permission');
        $this->assertFalse(Cache::has("laratrust_permissions_for_role_{$role->id}"));
    }
}
