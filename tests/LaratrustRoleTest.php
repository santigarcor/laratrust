<?php

namespace Laratrust\Test;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustRoleTest extends LaratrustTestCase
{
    protected $role;

    public function setUp()
    {
        parent::setUp();

        $this->migrate();
        $this->role = Role::create(['name' => 'role']);
    }

    public function testUsersRelationship()
    {
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\MorphToMany', $this->role->users());
    }

    public function testAccessUsersRelationshipAsAttribute()
    {
        $this->assertEmpty($this->role->users);
    }

    public function testPermissionsRelationship()
    {
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Relations\BelongsToMany', $this->role->permissions());
    }

    public function testHasPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);

        $this->role->permissions()->attach([$permA->id, $permB->id]);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->assertTrue($this->role->hasPermission('permission_a'));
        $this->assertTrue($this->role->hasPermission('permission_b'));
        $this->assertFalse($this->role->hasPermission('permission_c'));

        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_b']));
        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_c']));
        $this->assertFalse($this->role->hasPermission(['permission_a', 'permission_c'], true));
        $this->assertFalse($this->role->hasPermission(['permission_c', 'permission_d']));
    }

    public function testAttachPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);
        $permC = Permission::create(['name' => 'permission_c']);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->attachPermission($permA));
        $this->assertCount(1, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->attachPermission($permB->toArray()));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->attachPermission($permC->id));
        $this->assertCount(3, $this->role->permissions()->get()->toArray());

        if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException('InvalidArgumentException');
        } else {
            $this->expectException('InvalidArgumentException');
        }
        $this->role->attachPermission(true);
    }

    public function testDetachPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);
        $permC = Permission::create(['name' => 'permission_c']);
        $this->role->permissions()->attach([$permA->id, $permB->id, $permC->id]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->detachPermission($permA));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->detachPermission($permB->toArray()));
        $this->assertCount(1, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->detachPermission($permB->id));
        $this->assertCount(1, $this->role->permissions()->get()->toArray());
    }

    public function testAttachPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
        ];

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->attachPermissions($perms));
        $this->assertCount(3, $this->role->permissions()->get()->toArray());
    }

    public function testDetachPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
        ];
        $this->role->attachPermissions($perms);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->detachPermissions($perms));
        $this->assertCount(0, $this->role->permissions()->get()->toArray());
    }

    public function testDetachAllPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
        ];
        $this->role->attachPermissions($perms);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->detachPermissions());
        $this->assertCount(0, $this->role->permissions()->get()->toArray());
    }

    public function testSyncPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $perms = [
            Permission::create(['name' => 'permission_a'])->id,
            Permission::create(['name' => 'permission_b'])->id,
        ];
        $this->role->attachPermission(Permission::create(['name' => 'permission_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->syncPermissions($perms));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->role->syncPermissions([]);
        $this->role->syncPermissions(['permission_a', 'permission_b']);
        $this->assertCount(2, $this->role->permissions()->get()->toArray());
    }
}
