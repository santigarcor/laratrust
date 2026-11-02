<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laratrust\Events\PermissionAdded;
use Laratrust\Events\RoleAdded;
use Laratrust\Tests\Enums\Team as TeamEnum;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\UserWithCommonMethodNames;
use Mockery as m;

class UserTest extends LaratrustTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.teams.enabled', true);
    }

    public function test_roles_relationship(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertInstanceOf(MorphToMany::class, $this->user->roles());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(MorphToMany::class, $this->user->roles());
    }

    public function test_permissions_relationship(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertInstanceOf(MorphToMany::class, $this->user->permissions());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(MorphToMany::class, $this->user->permissions());
    }

    public function test_roles_teams(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertNull($this->user->rolesTeams());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(MorphToMany::class, $this->user->rolesTeams());
    }

    public function test_permissions_teams(): void
    {
        $team = Team::create(['name' => 'team_a']);

        $this->user->givePermissions([
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
        ], $team);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertNull($this->user->permissionsTeams());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(
            MorphToMany::class,
            $this->user->permissionsTeams()
        );
        $this->assertInstanceOf(
            Team::class,
            $this->user->permissionsTeams()->first()
        );
    }

    public function test_all_teams(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $teamA = Team::create(['name' => 'team_a']);
        $teamB = Team::create(['name' => 'team_b']);
        $this->user->givePermissions([
            Permission::create(['name' => 'permission_a']),
            Permission::create(['name' => 'permission_b']),
            Permission::create(['name' => 'permission_c']),
        ], $teamA);

        $this->user->addRoles([
            Role::create(['name' => 'role_a']),
            Role::create(['name' => 'role_b']),
            Role::create(['name' => 'role_c']),
        ], $teamB);

        $this->user->addRoles([
            Role::create(['name' => 'role_d']),
        ], $teamA);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(Collection::class, $this->user->allTeams());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertEmpty($this->user->allTeams());

        $this->app['config']->set('laratrust.teams.enabled', true);

        $this->assertSame(
            ['team_a', 'team_b'],
            $this->user->allTeams()->sortBy('name')->pluck('name')->all()
        );
        $onlySomeColumns = $this->user->allTeams(['name'])->first()->toArray();
        $this->assertArrayHasKey('id', $onlySomeColumns);
        $this->assertArrayHasKey('name', $onlySomeColumns);
        $this->assertArrayNotHasKey('displayName', $onlySomeColumns);
    }

    public function test_is_able_to(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock(User::class)->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasPermission')->with('manage_user', null, false)->andReturn(true)->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->isAbleTo('manage_user'));
        $this->assertFalse($user->isNotAbleTo('manage_user'));
    }

    public function test_adding_an_assignment_twice_does_nothing_the_second_time(): void
    {
        Event::fake([RoleAdded::class, PermissionAdded::class]);
        $role = Role::create(['name' => 'role_a']);
        $permission = Permission::create(['name' => 'permission_a']);
        $team = Team::create(['name' => 'team_a']);

        foreach ([false, true] as $teamsEnabled) {
            $this->app['config']->set('laratrust.teams.enabled', $teamsEnabled);

            $this->user->addRole($role)->addRole($role)->givePermission($permission)->givePermission($permission);
            $this->assertSame(1, $this->user->roles()->count());
            $this->assertSame(1, $this->user->permissions()->count());

            $this->user->roles()->sync([]);
            $this->user->permissions()->sync([]);
        }

        // In a team it's a different assignment.
        $this->user->addRole($role)->addRole($role, $team)->addRole($role, $team);
        $this->assertSame(2, $this->user->roles()->count());

        $role->givePermission($permission)->givePermission($permission);
        $this->assertSame(1, $role->permissions()->count());

        Event::assertDispatchedTimes(RoleAdded::class, 4);
        Event::assertDispatchedTimes(PermissionAdded::class, 3);
    }

    public function test_the_team_can_be_a_backed_enum_when_assigning(): void
    {
        $teamA = Team::create(['name' => 'team_a']);
        $teamB = Team::create(['name' => 'team_b']);
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $teamOf = fn (MorphToMany $relation): array => $relation->get()->pluck('pivot.team_id')->all();

        $this->user->addRole('role_a', TeamEnum::TEAM_A)->givePermission('permission_a', TeamEnum::TEAM_A);
        $this->assertSame([$teamA->id], $teamOf($this->user->roles()));
        $this->assertSame([$teamA->id], $teamOf($this->user->permissions()));
        $this->assertTrue($this->user->hasRole('role_a', TeamEnum::TEAM_A));
        $this->assertFalse($this->user->hasRole('role_a', TeamEnum::TEAM_B));

        $this->user->syncRoles([$roleB], TeamEnum::TEAM_B)->syncPermissions([$permissionB], TeamEnum::TEAM_B);
        $this->assertEqualsCanonicalizing([$teamA->id, $teamB->id], $teamOf($this->user->roles()));
        $this->assertEqualsCanonicalizing([$teamA->id, $teamB->id], $teamOf($this->user->permissions()));

        $this->user->removeRole($roleA, TeamEnum::TEAM_A)->removePermission($permissionA, TeamEnum::TEAM_A);
        $this->assertSame([$teamB->id], $teamOf($this->user->roles()));
        $this->assertSame([$teamB->id], $teamOf($this->user->permissions()));
        $this->assertSame(['role_b'], $this->user->getRoles());
    }

    public function test_add_role(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $role = Role::create(['name' => 'role_a']);
        $team = Team::create(['name' => 'team_a']);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can attach role by passing an object
        $this->assertWasAttached('role', $this->user->addRole($role));
        // Can attach role by passing an id
        $this->assertWasAttached('role', $this->user->addRole($role->id));
        // Can attach role by passing an array with 'id' => $id
        $this->assertWasAttached('role', $this->user->addRole($role->toArray()));
        // Can attach role by passing the role name
        $this->assertWasAttached('role', $this->user->addRole('role_a'));
        // Can attach role by passing the role and team
        $this->assertWasAttached('role', $this->user->addRole($role, $team));
        // Can attach role by passing the role and team id
        $this->assertInstanceOf(User::class, $this->user->addRole($role, $team->id));
        $this->assertEquals($team->id, $this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);
        // Can attach role by passing the role and team name
        $this->assertInstanceOf(User::class, $this->user->addRole($role, 'team_a'));
        $this->assertEquals($team->id, $this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasAttached('role', $this->user->addRole($role));

        $this->assertInstanceOf(User::class, $this->user->addRole($role, 'team_a'));
        $this->assertNull($this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);
    }

    public function test_remove_role(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $role = Role::create(['name' => 'role_a']);
        $this->user->roles()->attach($role->id);
        $team = Team::create(['name' => 'team_a']);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can attach role by passing an object
        $this->assertWasDetached('role', $this->user->removeRole($role), $role);
        // Can detach role by passing an id
        $this->assertWasDetached('role', $this->user->removeRole($role->id), $role);
        // Can detach role by passing an array with 'id' => $id
        $this->assertWasDetached('role', $this->user->removeRole($role->toArray()), $role);
        // Can detach role by passing the role name
        $this->assertInstanceOf(User::class, $this->user->removeRole('role_a'));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team
        $this->assertInstanceOf(User::class, $this->user->removeRole($role, $team));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team id
        $this->assertInstanceOf(User::class, $this->user->removeRole($role, $team->id));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team name
        $this->assertInstanceOf(User::class, $this->user->removeRole($role, 'team_a'));

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasDetached('role', $this->user->removeRole($role), $role);
        $this->assertWasDetached('role', $this->user->removeRole($role, 'TeamA'), $role);
    }

    public function test_add_roles(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock(User::class)->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('addRole')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(User::class, $user->addRoles([1, 2, 3]));
        $this->assertInstanceOf(User::class, $user->addRoles([1, 2, 3], 'TeamA'));
    }

    public function test_remove_roles(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock(User::class)->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        // An empty list removes nothing.
        $user->shouldNotReceive('syncRoles');
        $user
            ->shouldReceive('removeRole')
            ->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))
            ->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(User::class, $user->removeRoles([1, 2, 3]));
        $this->assertInstanceOf(User::class, $user->removeRoles([]));
        $this->assertInstanceOf(User::class, $user->removeRoles([1, 2, 3], 'TeamA'));
    }

    public function test_sync_roles(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Team::create(['name' => 'team_a']);
        $roles = [
            Role::create(['name' => 'role_a'])->id,
            Role::create(['name' => 'role_b']),
        ];
        $this->user->addRole(Role::create(['name' => 'role_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(User::class, $this->user->syncRoles($roles));
        $this->assertEquals(2, $this->user->roles()->count());
        $this->assertInstanceOf(User::class, $this->user->syncRoles($roles, 'team_a'));
        $this->assertEquals(4, $this->user->roles()->count());
        $this->assertInstanceOf(User::class, $this->user->syncRoles(['role_a']));
        $this->assertEquals(3, $this->user->roles()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->syncRoles([]);
        $this->assertInstanceOf(User::class, $this->user->syncRoles($roles));
        $this->assertEquals(2, $this->user->roles()->count());
        $this->assertInstanceOf(User::class, $this->user->syncRoles($roles, 'team_a', false));
        $this->assertEquals(2, $this->user->roles()->count());
    }

    public function test_sync_roles_without_detaching(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Team::create(['name' => 'team_a']);
        $roles = [
            Role::create(['name' => 'role_a'])->id,
            Role::create(['name' => 'role_b'])->id,
        ];
        $this->user->addRole(Role::create(['name' => 'role_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(User::class, $this->user->syncRolesWithoutDetaching($roles));
        $this->assertEquals(3, $this->user->roles()->count());
        $this->assertInstanceOf(User::class, $this->user->syncRolesWithoutDetaching($roles, 'team_a'));
        $this->assertEquals(5, $this->user->roles()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->removeRoles([1]);
        $this->assertInstanceOf(User::class, $this->user->syncRolesWithoutDetaching($roles));
        $this->assertEquals(4, $this->user->roles()->count());
        $this->assertInstanceOf(User::class, $this->user->syncRolesWithoutDetaching($roles, 'team_a'));
        $this->assertEquals(4, $this->user->roles()->count());
    }

    public function testgive_permission(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permission = Permission::create(['name' => 'permission_a']);
        $team = Team::create(['name' => 'team_a']);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can attach permission by passing an object
        $this->assertWasAttached('permission', $this->user->givePermission($permission));
        // Can attach permission by passing an id
        $this->assertWasAttached('permission', $this->user->givePermission($permission->id));
        // Can attach permission by passing an array with 'id' => $id
        $this->assertWasAttached('permission', $this->user->givePermission($permission->toArray()));
        // Can attach permission by passing the permission name
        $this->assertWasAttached('permission', $this->user->givePermission('permission_a'));
        // Can attach permission by passing the permission and team
        $this->assertWasAttached('permission', $this->user->givePermission($permission, $team));
        // Can attach permission by passing the permission and team id
        $this->assertInstanceOf(User::class, $this->user->givePermission($permission, $team->id));
        $this->assertEquals($team->id, $this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);
        // Can attach permission by passing the permission and team name
        $this->assertInstanceOf(User::class, $this->user->givePermission($permission, 'team_a'));
        $this->assertEquals($team->id, $this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasAttached('permission', $this->user->givePermission($permission));

        $this->assertInstanceOf(User::class, $this->user->givePermission($permission, 'team_a'));
        $this->assertNull($this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);
    }

    public function test_remove_permission(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permission = Permission::create(['name' => 'permission_a']);
        $this->user->permissions()->attach($permission->id);
        $team = Team::create(['name' => 'team_a']);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can attach permission by passing an object
        $this->assertWasDetached('permission', $this->user->removePermission($permission), $permission);
        // Can detach permission by passing an id
        $this->assertWasDetached('permission', $this->user->removePermission($permission->id), $permission);
        // Can detach permission by passing an array with 'id' => $id
        $this->assertWasDetached('permission', $this->user->removePermission($permission->toArray()), $permission);
        // Can detach permission by passing the permission name
        $this->assertInstanceOf(User::class, $this->user->removePermission('permission_a'));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team
        $this->assertInstanceOf(User::class, $this->user->removePermission($permission, $team));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team id
        $this->assertInstanceOf(User::class, $this->user->removePermission($permission, $team->id));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team name
        $this->assertInstanceOf(User::class, $this->user->removePermission($permission, 'team_a'));

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasDetached('permission', $this->user->removePermission($permission), $permission);
        $this->assertWasDetached('permission', $this->user->removePermission($permission, 'team_a'), $permission);
    }

    public function testgive_permissions(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock(User::class)->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('givePermission')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(User::class, $user->givePermissions([1, 2, 3]));
        $this->assertInstanceOf(User::class, $user->givePermissions([1, 2, 3], 'TeamA'));
    }

    public function test_remove_permissions(): void
    {
        $user = m::mock(User::class)->makePartial();

        // An empty list removes nothing.
        $user->shouldNotReceive('syncPermissions');
        $user->shouldReceive('removePermission')
            ->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))
            ->times(6);

        $this->assertInstanceOf(User::class, $user->removePermissions([1, 2, 3]));
        $this->assertInstanceOf(User::class, $user->removePermissions([]));
        $this->assertInstanceOf(User::class, $user->removePermissions([1, 2, 3], 'TeamA'));
    }

    public function syncPermissions(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Team::create(['name' => 'team_a']);
        $permissions = [
            Permission::create(['name' => 'permission_a'])->id,
            Permission::create(['name' => 'permission_b']),
        ];
        $this->user->givePermission(Permission::create(['name' => 'permission_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(User::class, $this->user->syncPermissions($permissions));
        $this->assertEquals(2, $this->user->permissions()->count());
        $this->assertInstanceOf(User::class, $this->user->syncPermissions($permissions, 'team_a'));
        $this->assertEquals(4, $this->user->permissions()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->syncPermissions([]);
        $this->assertInstanceOf(User::class, $this->user->syncPermissions($permissions));
        $this->assertEquals(2, $this->user->permissions()->count());
        $this->assertInstanceOf(User::class, $this->user->syncPermissions($permissions, 'team_a', false));
        $this->assertEquals(2, $this->user->permissions()->count());
    }

    public function test_sync_permissions_without_detaching(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Team::create(['name' => 'team_a']);
        $permissions = [
            Permission::create(['name' => 'permission_a'])->id,
            Permission::create(['name' => 'permission_b'])->id,
        ];
        $this->user->givePermission(Permission::create(['name' => 'permission_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(User::class, $this->user->syncPermissionsWithoutDetaching($permissions));
        $this->assertEquals(3, $this->user->permissions()->count());
        $this->assertInstanceOf(User::class, $this->user->syncPermissionsWithoutDetaching($permissions, 'team_a'));
        $this->assertEquals(5, $this->user->permissions()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->removePermissions([1]);
        $this->assertInstanceOf(User::class, $this->user->syncPermissionsWithoutDetaching($permissions));
        $this->assertEquals(4, $this->user->permissions()->count());
        $this->assertInstanceOf(User::class, $this->user->syncPermissionsWithoutDetaching($permissions, 'team_a'));
        $this->assertEquals(4, $this->user->permissions()->count());
    }

    public function test_all_permissions(): void
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

        $roleA->givePermissions([$permissionA, $permissionB]);
        $roleB->givePermissions([$permissionB, $permissionC]);
        $this->user->givePermissions([$permissionB, $permissionC]);
        $this->user->addRoles([$roleA, $roleB]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            ['permission_a', 'permission_b', 'permission_c'],
            $this->user->allPermissions()->sortBy('name')->pluck('name')->all()
        );

        $onlySomeColumns = $this->user->allPermissions(['name'])->first()->toArray();
        $this->assertArrayHasKey('id', $onlySomeColumns);
        $this->assertArrayHasKey('name', $onlySomeColumns);
        $this->assertArrayNotHasKey('displayName', $onlySomeColumns);
    }

    public function test_all_permissions_selects_qualified_columns(): void
    {
        $role = Role::create(['name' => 'role_a']);
        $role->givePermission(Permission::create(['name' => 'permission_a']));
        $this->user->addRole($role);
        $this->user->givePermission(Permission::create(['name' => 'permission_b']));

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->assertSame(['permission_a', 'permission_b'], $this->user->allPermissions(['name'])->pluck('name')->sort()->values()->all());
        foreach (array_filter($queries, fn ($sql): bool => str_contains($sql, 'from "permissions"')) as $sql) {
            $this->assertStringContainsString('"permissions"."name", "permissions"."id"', $sql);
        }
    }

    public function test_all_permissions_scoped_on_teams(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $roleC = Role::create(['name' => 'role_c']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);
        $permissionD = Permission::create(['name' => 'permission_d']);

        $teamA = Team::create(['name' => 'team_a']);
        $teamB = Team::create(['name' => 'team_b']);

        $roleA->givePermissions([$permissionA, $permissionB]);
        $roleB->givePermissions([$permissionB, $permissionC]);
        $roleC->givePermissions([$permissionD]);

        $this->user->givePermissions([$permissionB, $permissionC]);
        $this->user->givePermissions([$permissionC], $teamA);
        $this->user->addRole($roleA);
        $this->user->addRole($roleB, $teamA);
        $this->user->addRole($roleC, $teamB);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            ['permission_a', 'permission_b', 'permission_c', 'permission_d'],
            $this->user->allPermissions(null, false)->sortBy('name')->pluck('name')->all()
        );
        $this->assertSame(
            ['permission_a', 'permission_b', 'permission_c'],
            $this->user->allPermissions(null, null)->sortBy('name')->pluck('name')->all()
        );
        $this->assertSame(
            ['permission_b', 'permission_c'],
            $this->user->allPermissions(null, 'team_a')->sortBy('name')->pluck('name')->all()
        );

        $this->assertSame(
            ['permission_d'],
            $this->user->allPermissions(null, 'team_b')->sortBy('name')->pluck('name')->all()
        );
    }

    public function test_many_to_many_records_are_removed(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleA = Role::create(['name' => 'role_a']);
        $permissionA = Permission::create(['name' => 'permission_a']);

        $teamA = Team::create(['name' => 'team_a']);

        $this->user->addRole($roleA, $teamA);
        $this->user->givePermission($permissionA);

        // force-deleting because the user model has soft-deletes enabled
        $this->user->forceDelete();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertModelMissing($this->user);

        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.role_user'),
            ['user_id' => $this->user->id]
        );

        $this->assertDatabaseMissing(
            $this->app['config']->get('laratrust.tables.permission_user'),
            ['user_id' => $this->user->id]
        );
    }

    protected function assertWasAttached($objectName, $result)
    {
        $relationship = Str::plural($objectName);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $this->user->$relationship()->count());
        $this->user->$relationship()->sync([]);
    }

    protected function assertWasDetached($objectName, $result, $toReAttach)
    {
        $relationship = Str::plural($objectName);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(0, $this->user->$relationship()->count());
        $this->user->$relationship()->attach($toReAttach->id);
    }

    public function test_assigning_doesnt_depend_on_methods_the_user_model_could_define(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', true);
        $team = Team::create(['name' => 'team_a']);
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);
        $permission = Permission::create(['name' => 'permission_a']);
        $this->app['config']->set('laratrust.user_models.users_with_common_method_names', UserWithCommonMethodNames::class);
        $user = UserWithCommonMethodNames::find($this->user->id);

        $user->addRole($roleA, $team)->addRole($roleB)->removeRole($roleB)->givePermission($permission);
        $user->syncRoles([$roleA, $roleB], $team);

        $this->assertTrue($user->hasRole(['role_a', 'role_b'], $team, true));
        $this->assertTrue($user->isAbleTo('permission_a'));
    }
}
