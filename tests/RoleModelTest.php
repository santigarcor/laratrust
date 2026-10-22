<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\RoleWithCustomUsersRelation;
use Laratrust\Tests\Models\User;

class RoleModelTest extends LaratrustTestCase
{
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->role = Role::create(['name' => 'role']);
    }

    public function test_users_relationship()
    {
        $this->assertInstanceOf(MorphToMany::class, $this->role->users());
    }

    public function test_access_users_relationship_as_attribute()
    {
        $this->assertEmpty($this->role->users);
    }

    public function test_permissions_relationship()
    {
        $this->assertInstanceOf(BelongsToMany::class, $this->role->permissions());
    }

    public function testgive_permission()
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

    public function test_remove_permission()
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

    public function testgive_permissions()
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

    public function test_remove_permissions()
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

    public function test_detach_all_permissions()
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

    public function test_sync_permissions()
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

    public function test_deleting_role_removes_pivot_records()
    {
        $permission = Permission::create(['name' => 'permission_a']);
        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->role->givePermission($permission);
        $user->addRole($this->role);

        // force-deleting because the role model has soft-deletes enabled
        $this->role->forceDelete();

        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.role_user'),
            ['role_id' => $this->role->id]
        );
        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.permission_role'),
            ['role_id' => $this->role->id]
        );
    }

    public function test_deleting_role_with_overridden_users_relation_removes_pivot_records()
    {
        $role = RoleWithCustomUsersRelation::create(['name' => 'custom_role']);
        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $user->addRole($role);

        $role->delete();

        $this->assertModelMissing($role);
        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.role_user'),
            ['role_id' => $role->id]
        );
    }
}
