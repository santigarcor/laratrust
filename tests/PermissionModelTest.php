<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\PermissionWithCustomUsersRelation;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;

class PermissionModelTest extends LaratrustTestCase
{
    protected $permission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->permission = new Permission;
    }

    public function test_users_relationship(): void
    {
        $this->assertInstanceOf(MorphToMany::class, $this->permission->users());
    }

    public function test_access_users_relationship_as_attribute(): void
    {
        $this->assertEmpty($this->permission->users);
    }

    public function test_roles_relationship(): void
    {
        $this->assertInstanceOf(BelongsToMany::class, $this->permission->roles());
    }

    public function test_deleting_permission_removes_pivot_records(): void
    {
        $permission = Permission::create(['name' => 'permission_a']);
        $role = Role::create(['name' => 'role_a']);
        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $role->givePermission($permission);
        $user->givePermission($permission);

        // force-deleting because the permission model has soft-deletes enabled
        $permission->forceDelete();

        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.permission_user'),
            ['permission_id' => $permission->id]
        );
        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.permission_role'),
            ['permission_id' => $permission->id]
        );
    }

    public function test_deleting_permission_with_overridden_users_relation_removes_pivot_records(): void
    {
        $permission = PermissionWithCustomUsersRelation::create(['name' => 'custom_permission']);
        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $user->givePermission($permission);

        $permission->delete();

        $this->assertModelMissing($permission);
        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.permission_user'),
            ['permission_id' => $permission->id]
        );
    }
}
