<?php

declare(strict_types=1);

namespace Laratrust\Test;

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
        $this->permission = new Permission();
    }

    public function testUsersRelationship()
    {
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\MorphToMany', $this->permission->users());
    }

    public function testAccessUsersRelationshipAsAttribute()
    {
        $this->assertEmpty($this->permission->users);
    }

    public function testRolesRelationship()
    {
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Relations\BelongsToMany', $this->permission->roles());
    }

    public function testDeletingPermissionRemovesPivotRecords()
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

    public function testDeletingPermissionWithOverriddenUsersRelationRemovesPivotRecords()
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
