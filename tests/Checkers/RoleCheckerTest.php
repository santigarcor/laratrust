<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers;

use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;

class RoleCheckerTest extends LaratrustTestCase
{
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();

        $this->role = Role::create(['name' => 'role']);
    }

    public function test_has_permission(): void
    {
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);

        $this->role->permissions()->attach([$permA->id, $permB->id]);

        $this->assertTrue($this->role->hasPermission(EnumsPermission::PERM_A));
        $this->assertTrue($this->role->hasPermission('permission_b'));
        $this->assertFalse($this->role->hasPermission('permission_c'));

        $this->assertFalse($this->role->doesntHavePermission(EnumsPermission::PERM_A));
        $this->assertFalse($this->role->doesntHavePermission('permission_b'));
        $this->assertTrue($this->role->doesntHavePermission('permission_c'));

        $this->assertTrue($this->role->hasPermission([EnumsPermission::PERM_A, 'permission_b']));
        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_c']));
        $this->assertFalse($this->role->hasPermission([EnumsPermission::PERM_A, 'permission_c'], true));
        $this->assertFalse($this->role->hasPermission(['permission_c', 'permission_d']));

        $this->assertFalse($this->role->doesntHavePermission([EnumsPermission::PERM_A, 'permission_b']));
        $this->assertFalse($this->role->doesntHavePermission(['permission_a', 'permission_c']));
        $this->assertTrue($this->role->doesntHavePermission([EnumsPermission::PERM_A, 'permission_c'], true));
        $this->assertTrue($this->role->doesntHavePermission(['permission_c', 'permission_d']));
    }

    public function test_role_disable_the_permissions_caching(): void
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
        $this->assertTrue(Cache::has("laratrust_role_{$role->id}"));
        $role->flushLaratrustCache();

        // Without cache
        $this->app['config']->set('laratrust.cache.enabled', false);
        $role->hasPermission('some_permission');
        $this->assertFalse(Cache::has("laratrust_role_{$role->id}"));
    }
}
