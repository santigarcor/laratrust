<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\Enums\Role as EnumsRole;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\UserWithCommonMethodNames;

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

    public function test_scope_where_role_is(): void
    {
        $roleA = Role::create(['name' => EnumsRole::ROLE_A]);
        $roleB = Role::create(['name' => 'role_b']);
        Role::create(['name' => 'role_c']);
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

    public function test_scope_or_where_role_is(): void
    {
        $roleA = Role::create(['name' => 'role_a']);
        Role::create(['name' => 'role_c']);
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

    public function test_scope_where_permission_is(): void
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => EnumsPermission::PERM_A]);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        Permission::create(['name' => 'permission_d']);

        $roleA->givePermissions([$permissionA, $permissionB]);
        $roleB->givePermissions([$permissionB, $permissionC]);
        $this->user->givePermissions([$permissionB, $permissionC]);
        $this->user->addRoles([$roleA, $roleB]);

        $this->assertCount(1, User::whereHasPermission(EnumsPermission::PERM_A)->get());
        $this->assertCount(1, User::whereHasPermission('permission_c')->get());
        $this->assertCount(1, User::whereHasPermission(['permission_c', 'permission_d'])->get());
        $this->assertCount(0, User::whereHasPermission('permission_d')->get());
    }

    public function test_scope_or_where_has_permission(): void
    {
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        Permission::create(['name' => 'permission_d']);

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

    public function test_scope_to_retrieve_the_users_that_dont_have_roles(): void
    {
        $roleA = Role::create(['name' => 'role_a']);
        $this->user->addRoles([$roleA]);
        $userWithoutRole = User::create(['name' => 'test2', 'email' => 'test2@test.com']);

        $this->assertEquals($userWithoutRole->id, User::whereDoesntHaveRoles()->first()->id);
        $this->assertCount(1, User::whereDoesntHaveRoles()->get());
    }

    public function test_scope_to_retrieve_the_users_that_dont_have_permissions(): void
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

        // Only a direct permission, no roles: they do have a permission (8.x included them).
        $userWithOnlyADirectPermission = User::create(['name' => 'test3', 'email' => 'test3@test.com']);
        $userWithOnlyADirectPermission->givePermission($permissionB);

        $this->assertEquals($userWithoutPerms->id, User::whereDoesntHavePermissions()->first()->id);
        $this->assertCount(1, User::whereDoesntHavePermissions()->get());
    }

    public function test_scope_where_permission_is_for_team(): void
    {
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        Permission::create(['name' => 'permission_c']);
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

    public function test_scopes_with_teams_when_the_roles_and_permissions_tables_have_a_team_id_column(): void
    {
        // #695: an unqualified team_id was ambiguous, and the role path filtered the wrong table.
        Schema::table('roles', fn (Blueprint $table) => $table->unsignedInteger('team_id')->nullable());
        Schema::table('permissions', fn (Blueprint $table) => $table->unsignedInteger('team_id')->nullable());

        $teamA = Team::create(['name' => 'team_a']);
        $teamB = Team::create(['name' => 'team_b']);
        $role = Role::create(['name' => 'role_a']);
        $role->givePermission(Permission::create(['name' => 'through_role']));
        $this->user->addRole($role, $teamA);
        $this->user->givePermission(Permission::create(['name' => 'direct']), $teamA);

        $this->assertCount(1, User::whereHasRole('role_a', $teamA)->get());
        $this->assertCount(0, User::whereHasRole('role_a', $teamB)->get());
        $this->assertCount(1, User::whereHasPermission('through_role', $teamA)->get());
        $this->assertCount(0, User::whereHasPermission('through_role', $teamB)->get());
        $this->assertCount(1, User::whereHasPermission('direct', 'team_a')->get());
        $this->assertCount(0, User::whereHasPermission('direct', 'team_b')->get());
        $this->assertCount(1, User::whereHasRole('missing')->orWhereHasPermission('direct', $teamA)->get());
    }

    public function test_scopes_ignore_the_team_when_teams_are_disabled(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', false);

        foreach ([
            User::whereHasRole('role_a', 'team_a'),
            User::whereHasPermission('permission_a', 'team_a'),
        ] as $query) {
            $this->assertStringNotContainsString('team_id', $query->toSql());
        }
    }

    public function test_scopes_with_strict_check_and_no_team_only_match_assignments_without_a_team(): void
    {
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $team = Team::create(['name' => 'team_a']);
        $role = Role::create(['name' => 'role_a']);
        $role->givePermission(Permission::create(['name' => 'permission_a']));
        $this->user->addRole($role, $team);

        $this->assertCount(0, User::whereHasRole('role_a')->get());
        $this->assertCount(0, User::whereHasPermission('permission_a')->get());

        $this->user->addRole($role);

        $this->assertCount(1, User::whereHasRole('role_a')->get());
        $this->assertCount(1, User::whereHasPermission('permission_a')->get());
    }

    public function test_the_scopes_dont_depend_on_methods_the_user_model_could_define(): void
    {
        $team = Team::create(['name' => 'team_a']);
        $role = Role::create(['name' => 'role_a']);
        $role->givePermission(Permission::create(['name' => 'permission_a']));
        $this->user->addRole($role, $team);

        $this->app['config']->set('laratrust.user_models.users_with_common_method_names', UserWithCommonMethodNames::class);
        $user = UserWithCommonMethodNames::find($this->user->id);

        $this->assertTrue(UserWithCommonMethodNames::whereHasRole('role_a', $team)->first()->is($user));
        $this->assertTrue(UserWithCommonMethodNames::whereHasPermission('permission_a', $team)->first()->is($user));
    }
}
