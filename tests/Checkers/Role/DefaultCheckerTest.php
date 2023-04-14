<?php

declare(strict_types=1);

namespace Laratrust\Test\Checkers\Role;

use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;

class DefaultCheckerTest extends LaratrustTestCase
{
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->app['config']->set('laratrust.checker', 'default');

        $this->role = Role::create(['name' => 'role']);
    }

    public function testHasPermission()
    {
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);

        $this->role->permissions()->attach([$permA->id, $permB->id]);

        $this->assertTrue($this->role->hasPermission(EnumsPermission::PERM_A));
        $this->assertTrue($this->role->hasPermission('permission_b'));
        $this->assertFalse($this->role->hasPermission('permission_c'));

        $this->assertTrue($this->role->hasPermission([EnumsPermission::PERM_A, 'permission_b']));
        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_c']));
        $this->assertFalse($this->role->hasPermission([EnumsPermission::PERM_A, 'permission_c'], true));
        $this->assertFalse($this->role->hasPermission(['permission_c', 'permission_d']));
    }
}
