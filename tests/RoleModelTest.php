<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Illuminate\Contracts\Database\Query\Builder;
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

    public function test_users_relationship(): void
    {
        $this->assertInstanceOf(MorphToMany::class, $this->role->users());
    }

    public function test_access_users_relationship_as_attribute(): void
    {
        $this->assertEmpty($this->role->users);
    }

    public function test_users_relationship_can_be_eager_loaded(): void
    {
        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $user->addRole($this->role);

        $role = Role::with('users')->find($this->role->id);

        $this->assertTrue($role->relationLoaded('users'));
        $this->assertTrue($role->users->first()->is($user));
        $this->assertTrue(Role::whereHas('users', fn (Builder $query) => $query->whereKey($user->id))->exists());
    }

    public function test_permissions_relationship(): void
    {
        $this->assertInstanceOf(BelongsToMany::class, $this->role->permissions());
    }

    public function testgive_permission(): void
    {
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);
        $permC = Permission::create(['name' => 'permission_c']);
        Permission::create(['name' => 'permission_d']);

        $this->assertInstanceOf(Role::class, $this->role->givePermission($permA));
        $this->assertCount(1, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf(Role::class, $this->role->givePermission($permB->toArray()));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf(Role::class, $this->role->givePermission($permC->id));
        $this->assertCount(3, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf(Role::class, $this->role->givePermission(EnumsPermission::PERM_D));
        $this->assertCount(4, $this->role->permissions()->get()->toArray());

        $this->expectException('TypeError');
        $this->role->givePermission(true);
    }

    public function test_remove_permission(): void
    {
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);
        $permC = Permission::create(['name' => 'permission_c']);
        $permD = Permission::create(['name' => 'permission_d']);
        $this->role->permissions()->attach([$permA->id, $permB->id, $permC->id, $permD->id]);

        $this->assertInstanceOf(Role::class, $this->role->removePermission($permA));
        $this->assertCount(3, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf(Role::class, $this->role->removePermission($permB->toArray()));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf(Role::class, $this->role->removePermission($permB->id));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->assertInstanceOf(Role::class, $this->role->removePermission(EnumsPermission::PERM_D));
        $this->assertCount(1, $this->role->permissions()->get()->toArray());
    }

    public function testgive_permissions(): void
    {
        Permission::create(['name' => 'permission_d']);
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
            EnumsPermission::PERM_D,
        ];

        $this->assertInstanceOf(Role::class, $this->role->givePermissions($perms));
        $this->assertCount(4, $this->role->permissions()->get()->toArray());
    }

    public function test_remove_permissions(): void
    {
        Permission::create(['name' => 'permission_d']);
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
            EnumsPermission::PERM_D,
        ];
        $this->role->givePermissions($perms);

        $this->assertInstanceOf(Role::class, $this->role->removePermissions($perms));
        $this->assertCount(0, $this->role->permissions()->get()->toArray());
    }

    public function test_detach_all_permissions(): void
    {
        $perms = [
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
        ];
        $this->role->givePermissions($perms);

        $this->assertInstanceOf(Role::class, $this->role->removePermissions());
        $this->assertCount(0, $this->role->permissions()->get()->toArray());
    }

    public function test_sync_permissions(): void
    {
        $perms = [
            Permission::create(['name' => 'permission_a'])->id,
            Permission::create(['name' => 'permission_b'])->id,
        ];
        $this->role->givePermission(Permission::create(['name' => 'permission_c']));

        $this->assertInstanceOf(Role::class, $this->role->syncPermissions($perms));
        $this->assertCount(2, $this->role->permissions()->get()->toArray());

        $this->role->syncPermissions([]);
        $this->role->syncPermissions(['permission_a', 'permission_b']);
        $this->assertCount(2, $this->role->permissions()->get()->toArray());
    }

    public function test_deleting_role_removes_pivot_records(): void
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

    public function test_deleting_role_with_overridden_users_relation_removes_pivot_records(): void
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
