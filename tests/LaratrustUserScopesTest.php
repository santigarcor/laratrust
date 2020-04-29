<?php

namespace Laratrust\Test;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustUserScopesTest extends LaratrustTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.use_teams', true);
    }


    public function testScopeWhereRoleIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
         */
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $roleC = Role::create(['name' => 'role_c']);
        $roleD = Role::create(['name' => 'role_d']);
        $team = Team::create(['name' => 'team_a']);

        $this->user->attachRoles([$roleA, $roleB]);
        $this->user->attachRole($roleD, $team->id);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
         */
        $this->assertCount(1, User::whereRoleIs('role_a')->get());
        $this->assertCount(1, User::whereRoleIs(['role_a', 'role_c'])->get());
        $this->assertCount(0, User::whereRoleIs('role_c')->get());
        $this->assertCount(0, User::whereRoleIs(['role_c', 'role_x'])->get());

        $this->assertCount(1, User::whereRoleIs('role_d', 'team_a')->get());

        $this->app['config']->set('laratrust.teams_strict_check', true);
        $this->assertCount(0, User::whereRoleIs('role_d')->get());
        $this->assertCount(0, User::whereRoleIs(['role_d', 'role_c'])->get());
        $this->app['config']->set('laratrust.teams_strict_check', false);
        $this->assertCount(1, User::whereRoleIs('role_d')->get());
        $this->assertCount(1, User::whereRoleIs(['role_d', 'role_c'])->get());
    }

    public function testScopeOrWhereRoleIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
         */
        $roleA = Role::create(['name' => 'role_a']);
        $roleC = Role::create(['name' => 'role_c']);

        $this->user->attachRole($roleA);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
         */
        $this->assertCount(
            1,
            User::query()
                ->whereRoleIs('role_a')
                ->orWhereRoleIs('role_c')
                ->get()
        );
        $this->assertCount(
            0,
            User::query()
                ->whereRoleIs('role_d')
                ->orWhereRoleIs('role_c')
                ->get()
        );
    }

    public function testScopeWherePermissionIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
         */
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        $permissionD = Permission::create(['name' => 'permission_d']);

        $roleA->attachPermissions([$permissionA, $permissionB]);
        $roleB->attachPermissions([$permissionB, $permissionC]);
        $this->user->attachPermissions([$permissionB, $permissionC]);
        $this->user->attachRoles([$roleA, $roleB]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
         */
        $this->assertCount(1, User::wherePermissionIs('permission_a')->get());
        $this->assertCount(1, User::wherePermissionIs('permission_c')->get());
        $this->assertCount(0, User::wherePermissionIs('permission_d')->get());
    }

    public function testScopeOrWherePermissionIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
         */
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        $permissionD = Permission::create(['name' => 'permission_d']);

        $roleA->attachPermissions([$permissionA, $permissionB]);
        $roleB->attachPermissions([$permissionB, $permissionC]);
        $this->user->attachPermissions([$permissionB, $permissionC]);
        $this->user->attachRoles([$roleA, $roleB]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
         */
        $this->assertCount(
            1,
            User::query()
                ->wherePermissionIs('permission_a')
                ->orWherePermissionIs('permission_d')
                ->get()
        );
        $this->assertCount(
            1,
            User::query()
                ->wherePermissionIs('permission_c')
                ->orWherePermissionIs('permission_d')
                ->get()
        );
        $this->assertCount(
            0,
            User::query()
                ->orWherePermissionIs('permission_e')
                ->orWherePermissionIs('permission_d')
                ->get()
        );
    }
}
