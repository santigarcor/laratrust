<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;

class RoleModelTest extends LaratrustTestCase
{
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->role = Role::create(['name' => 'role']);
    }

    public function testUsersRelationship()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphToMany::class, $this->role->users());
    }

    public function testAccessUsersRelationshipAsAttribute()
    {
        $this->assertEmpty($this->role->users);
    }

    public function testPermissionsRelationship()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $this->role->permissions());
    }

    public function testgivePermission()
    {
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);
        $permC = Permission::create(['name' => 'permission_c']);
        Permission::create(['name' => 'permission_d']);

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->givePermission($permA));
        $this->assertCount(1, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->givePermission($permB->toArray()));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->givePermission($permC->id));
        $this->assertCount(3, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->givePermission(EnumsPermission::PERM_D));
        $this->assertCount(4, $this->role->permissions()->get()->toArray());

        $this->expectException('TypeError');
        $this->role->givePermission(true);
    }

    public function testRemovePermission()
    {
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);
        $permC = Permission::create(['name' => 'permission_c']);
        $permD = Permission::create(['name' => 'permission_d']);
        $this->role->permissions()->attach([$permA->id, $permB->id, $permC->id, $permD->id]);

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->removePermission($permA));
        $this->assertCount(3, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->removePermission($permB->toArray()));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->removePermission($permB->id));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->removePermission(EnumsPermission::PERM_D));
        $this->assertCount(1, $this->role->permissions()->get()->toArray());
    }

    public function testgivePermissions()
    {
        Permission::create(['name' => 'permission_d']);
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
            EnumsPermission::PERM_D,
        ];

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->givePermissions($perms));
        $this->assertCount(4, $this->role->permissions()->get()->toArray());
    }

    public function testRemovePermissions()
    {
        Permission::create(['name' => 'permission_d']);
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
            EnumsPermission::PERM_D,
        ];
        $this->role->givePermissions($perms);

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->removePermissions($perms));
        $this->assertCount(0, $this->role->permissions()->get()->toArray());
    }

    public function testDetachAllPermissions()
    {
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
        ];
        $this->role->givePermissions($perms);

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->removePermissions());
        $this->assertCount(0, $this->role->permissions()->get()->toArray());
    }

    public function testSyncPermissions()
    {
        $perms = [
            Permission::create(['name' => 'permission_a'])->id,
            Permission::create(['name' => 'permission_b'])->id,
        ];
        $this->role->givePermission(Permission::create(['name' => 'permission_c']));

        $this->assertInstanceOf('Laratrust\Tests\Models\Role', $this->role->syncPermissions($perms));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->role->syncPermissions([]);
        $this->role->syncPermissions(['permission_a', 'permission_b']);
        $this->assertCount(2, $this->role->permissions()->get()->toArray());
    }
}
