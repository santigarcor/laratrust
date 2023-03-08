<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class UserScopesTest extends LaratrustTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.teams.enabled', true);
    }


    public function testScopeWhereRoleIs()
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $roleC = Role::create(['name' => 'role_c']);
        $roleD = Role::create(['name' => 'role_d']);
        $team = Team::create(['name' => 'team_a']);
        $this->user->addRoles([$roleA, $roleB]);
        $this->user->addRole($roleD, $team->id);

        $this->assertCount(1, User::whereHasRole(['role_a', 'role_c'])->get());
        $this->assertCount(0, User::whereHasRole('role_c')->get());
        $this->assertCount(0, User::whereHasRole(['role_c', 'role_x'])->get());

        $this->assertCount(1, User::whereHasRole('role_d', 'team_a')->get());

        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertCount(0, User::whereHasRole('role_d')->get());
        $this->assertCount(0, User::whereHasRole(['role_d', 'role_c'])->get());
        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertCount(1, User::whereHasRole('role_d')->get());
        $this->assertCount(1, User::whereHasRole(['role_d', 'role_c'])->get());
    }

    public function testScopeOrWhereRoleIs()
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleC = Role::create(['name' => 'role_c']);
        $this->user->addRole($roleA);

        $this->assertCount(
            1,
            User::query()
                ->whereHasRole('role_a')
                ->orWhereHasRole('role_c')
                ->get()
        );
        $this->assertCount(
            0,
            User::query()
                ->whereHasRole('role_d')
                ->orWhereHasRole('role_c')
                ->get()
        );
    }

    public function testScopeWherePermissionIs()
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        $permissionD = Permission::create(['name' => 'permission_d']);

        $roleA->givePermissions([$permissionA, $permissionB]);
        $roleB->givePermissions([$permissionB, $permissionC]);
        $this->user->addPermissions([$permissionB, $permissionC]);
        $this->user->addRoles([$roleA, $roleB]);

        $this->assertCount(1, User::whereHasPermission('permission_a')->get());
        $this->assertCount(1, User::whereHasPermission('permission_c')->get());
        $this->assertCount(1, User::whereHasPermission(['permission_c', 'permission_d'])->get());
        $this->assertCount(0, User::whereHasPermission('permission_d')->get());
    }

    public function testScopeOrWhereHasPermission()
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        $permissionD = Permission::create(['name' => 'permission_d']);

        $roleA->givePermissions([$permissionA, $permissionB]);
        $roleB->givePermissions([$permissionB, $permissionC]);
        $this->user->addPermissions([$permissionB, $permissionC]);
        $this->user->addRoles([$roleA, $roleB]);

        $this->assertCount(
            1,
            User::query()
                ->whereHasPermission('permission_a')
                ->orWhereHasPermission('permission_d')
                ->get()
        );
        $this->assertCount(
            1,
            User::query()
                ->whereHasPermission('permission_c')
                ->orWhereHasPermission('permission_d')
                ->get()
        );
        $this->assertCount(
            0,
            User::query()
                ->orWhereHasPermission('permission_e')
                ->orWhereHasPermission('permission_d')
                ->get()
        );
    }

    public function testScopeToRetrieveTheUsersThatDontHaveRoles()
    {
        $roleA = Role::create(['name' => 'role_a']);
        $this->user->addRoles([$roleA]);
        $userWithoutRole = User::create(['name' => 'test2', 'email' => 'test2@test.com']);

        $this->assertEquals($userWithoutRole->id, User::whereDoesntHaveRoles()->first()->id);
        $this->assertCount(1, User::whereDoesntHaveRoles()->get());
    }

    public function testScopeToRetrieveTheUsersThatDontHavePermissions()
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);

        $roleA->givePermissions([$permissionA]);
        $this->user->addPermissions([$permissionB]);
        $this->user->addRoles([$roleA]);
        $userWithoutPerms = User::create(['name' => 'test2', 'email' => 'test2@test.com']);
        $userWithoutPerms->addRole($roleB);

        $this->assertEquals($userWithoutPerms->id, User::whereDoesntHavePermissions()->first()->id);
        $this->assertCount(1, User::whereDoesntHavePermissions()->get());
    }
}
