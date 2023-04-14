<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\Enums\Role as EnumsRole;
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
        $roleA = Role::create(['name' => EnumsRole::ROLE_A]);
        $roleB = Role::create(['name' => 'role_b']);
        $roleC = Role::create(['name' => 'role_c']);
        $roleD = Role::create(['name' => 'role_d']);
        $team = Team::create(['name' => 'team_a']);
        $this->user->addRoles([$roleA, $roleB]);
        $this->user->addRole($roleD, $team->id);

        $this->assertCount(1, User::whereHasRole([EnumsRole::ROLE_A, 'role_c'])->get());
        $this->assertCount(1, User::whereHasRole([EnumsRole::ROLE_A, 'role_c'])->get());
        $this->assertCount(0, User::whereHasRole(EnumsRole::ROLE_C)->get());
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
                ->orWhereHasRole(EnumsRole::ROLE_C)
                ->get()
        );
        $this->assertCount(
            0,
            User::query()
                ->whereHasRole('role_d')
                ->orWhereHasRole(EnumsRole::ROLE_C)
                ->get()
        );
    }

    public function testScopeWherePermissionIs()
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => EnumsPermission::PERM_A]);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        $permissionD = Permission::create(['name' => 'permission_d']);

        $roleA->givePermissions([$permissionA, $permissionB]);
        $roleB->givePermissions([$permissionB, $permissionC]);
        $this->user->givePermissions([$permissionB, $permissionC]);
        $this->user->addRoles([$roleA, $roleB]);

        $this->assertCount(1, User::whereHasPermission(EnumsPermission::PERM_A)->get());
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
        $this->user->givePermissions([$permissionB, $permissionC]);
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
                ->whereHasPermission(EnumsPermission::PERM_C)
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
        $this->user->givePermissions([$permissionB]);
        $this->user->addRoles([$roleA]);
        $userWithoutPerms = User::create(['name' => 'test2', 'email' => 'test2@test.com']);
        $userWithoutPerms->addRole($roleB);

        $this->assertEquals($userWithoutPerms->id, User::whereDoesntHavePermissions()->first()->id);
        $this->assertCount(1, User::whereDoesntHavePermissions()->get());
    }

    public function testScopeWherePermissionIsForTeam()
    {
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        $roleB = Role::create(['name' => 'role_b']);
        $teamA = Team::create(['name' => 'team_a']);
        $teamB = Team::create(['name' => 'team_b']);

        $roleB->givePermissions([$permissionB]);
        $this->user->givePermissions([$permissionA], $teamA);
        $this->user->addRoles([$roleB], $teamA);

        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertCount(1, User::whereHasPermission(['permission_a'], $teamA)->get());
        $this->assertCount(1, User::whereHasPermission(['permission_b'], $teamA)->get());
        $this->assertCount(0, User::whereHasPermission(['permission_a'], $teamB)->get());
        $this->assertCount(0, User::whereHasPermission(['permission_b'], $teamB)->get());
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertCount(0, User::whereHasPermission(['permission_a'])->get());
        $this->assertCount(0, User::whereHasPermission(['permission_b'])->get());
        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertCount(1, User::whereHasPermission(['permission_a'])->get());
        $this->assertCount(1, User::whereHasPermission(['permission_b'])->get());
    }
}
