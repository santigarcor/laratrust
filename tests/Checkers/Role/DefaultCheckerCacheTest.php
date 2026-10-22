<?php

declare(strict_types=1);

namespace Laratrust\Test\Checkers\Role;

use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;

class DefaultCheckerCacheTest extends LaratrustTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
    }

    public function test_user_disable_the_roles_and_permissions_caching()
    {
        $role = Role::create(['name' => 'role_a'])
            ->givePermissions([
                Permission::create(['name' => 'permission_a']),
                Permission::create(['name' => 'permission_b']),
                Permission::create(['name' => 'permission_c']),
            ]);

        // With cache
        $this->app['config']->set('laratrust.cache.enabled', true);
        $role->hasPermission('some_permission');
        $this->assertTrue(Cache::has("laratrust_permissions_for_role_{$role->id}"));
        $role->flushLaratrustCache();

        // Without cache
        $this->app['config']->set('laratrust.cache.enabled', false);
        $role->hasPermission('some_permission');
        $this->assertFalse(Cache::has("laratrust_permissions_for_role_{$role->id}"));
    }
}
