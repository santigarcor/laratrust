<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Mockery as m;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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

    public function testRolesRelationship()
    {
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertInstanceOf(MorphToMany::class, $this->user->roles());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(MorphToMany::class, $this->user->roles());
    }

    public function testPermissionsRelationship()
    {
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertInstanceOf(MorphToMany::class, $this->user->permissions());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(MorphToMany::class, $this->user->permissions());
    }

    public function testRolesTeams()
    {
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertNull($this->user->rolesTeams());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(MorphToMany::class, $this->user->rolesTeams());
    }


    public function testPermissionsTeams()
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
            '\Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->permissionsTeams()
        );
        $this->assertInstanceOf(
            Team::class,
            $this->user->permissionsTeams()->first()
        );
    }


    public function testAllTeams()
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
            Permission::create(['name' => 'permission_c'])
        ], $teamA);

        $this->user->addRoles([
            Role::create(['name' => 'role_a']),
            Role::create(['name' => 'role_b']),
            Role::create(['name' => 'role_c'])
        ], $teamB);

        $this->user->addRoles([
            Role::create(['name' => 'role_d']),
        ], $teamA);


        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(  '\Illuminate\Database\Eloquent\Collection', $this->user->allTeams());


        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertEmpty($this->user->allTeams());

        $this->app['config']->set('laratrust.teams.enabled', true);

        $this->assertSame(
            ['team_a', 'team_b',],
            $this->user->allTeams()->sortBy('name')->pluck('name')->all()
        );
        $onlySomeColumns = $this->user->allTeams(['name'])->first()->toArray();
        $this->assertArrayHasKey('id', $onlySomeColumns);
        $this->assertArrayHasKey('name', $onlySomeColumns);
        $this->assertArrayNotHasKey('displayName', $onlySomeColumns);

    }

    public function testIsAbleTo()
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
        $user->shouldReceive('hasPermission')->with('manage_user', null, false)->andReturn(true)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->isAbleTo('manage_user'));
    }

    public function testAddRole()
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->addRole($role, $team->id));
        $this->assertEquals($team->id, $this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);
        // Can attach role by passing the role and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->addRole($role, 'team_a'));
        $this->assertEquals($team->id, $this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasAttached('role', $this->user->addRole($role));

        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->addRole($role, 'team_a'));
        $this->assertNull($this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);
    }

    public function testRemoveRole()
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removeRole('role_a'));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removeRole($role, $team));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team id
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removeRole($role, $team->id));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removeRole($role, 'team_a'));

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasDetached('role', $this->user->removeRole($role), $role);
        $this->assertWasDetached('role', $this->user->removeRole($role, 'TeamA'), $role);
    }

    public function testAddRoles()
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->addRoles([1, 2, 3]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->addRoles([1, 2, 3], 'TeamA'));
    }

    public function testRemoveRoles()
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
        $user->shouldReceive('syncRoles')
            ->with([], null)
            ->andReturn($user)
            ->once();
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

    public function testSyncRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRoles($roles));
        $this->assertEquals(2, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRoles($roles, 'team_a'));
        $this->assertEquals(4, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRoles(['role_a']));
        $this->assertEquals(3, $this->user->roles()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->syncRoles([]);
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRoles($roles, null));
        $this->assertEquals(2, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRoles($roles, 'team_a', false));
        $this->assertEquals(2, $this->user->roles()->count());
    }

    public function testSyncRolesWithoutDetaching()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles));
        $this->assertEquals(3, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles, 'team_a'));
        $this->assertEquals(5, $this->user->roles()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->removeRoles([1]);
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles, null));
        $this->assertEquals(4, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles, 'team_a', false));
        $this->assertEquals(4, $this->user->roles()->count());
    }

    public function testgivePermission()
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->givePermission($permission, $team->id));
        $this->assertEquals($team->id, $this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);
        // Can attach permission by passing the permission and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->givePermission($permission, 'team_a'));
        $this->assertEquals($team->id, $this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasAttached('permission', $this->user->givePermission($permission));

        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->givePermission($permission, 'team_a'));
        $this->assertNull($this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);
    }

    public function testRemovePermission()
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removePermission('permission_a'));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removePermission($permission, $team));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team id
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removePermission($permission, $team->id));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->removePermission($permission, 'team_a'));

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertWasDetached('permission', $this->user->removePermission($permission), $permission);
        $this->assertWasDetached('permission', $this->user->removePermission($permission, 'team_a'), $permission);
    }

    public function testgivePermissions()
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->givePermissions([1, 2, 3]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->givePermissions([1, 2, 3], 'TeamA'));
    }

    public function testRemovePermissions()
    {
        $user = m::mock(User::class)->makePartial();

        $user->shouldReceive('syncPermissions')
            ->andReturn($user)
            ->with([], null)
            ->once();
        $user->shouldReceive('removePermission')
            ->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))
            ->times(6);

        $this->assertInstanceOf(User::class, $user->removePermissions([1, 2, 3]));
        $this->assertInstanceOf(User::class, $user->removePermissions([]));
        $this->assertInstanceOf(User::class, $user->removePermissions([1, 2, 3], 'TeamA'));
    }

    public function syncPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissions($permissions));
        $this->assertEquals(2, $this->user->permissions()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissions($permissions, 'team_a'));
        $this->assertEquals(4, $this->user->permissions()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->syncPermissions([]);
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissions($permissions, null));
        $this->assertEquals(2, $this->user->permissions()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissions($permissions, 'team_a', false));
        $this->assertEquals(2, $this->user->permissions()->count());
    }


    public function testSyncPermissionsWithoutDetaching()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
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
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions));
        $this->assertEquals(3, $this->user->permissions()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions, 'team_a'));
        $this->assertEquals(5, $this->user->permissions()->count());

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->user->removePermissions([1]);
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions, null));
        $this->assertEquals(4, $this->user->permissions()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions, 'team_a', false));
        $this->assertEquals(4, $this->user->permissions()->count());
    }

    public function testAllPermissions()
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

    public function testAllPermissionsScopedOnTeams()
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
            ['permission_d',],
            $this->user->allPermissions(null, 'team_b')->sortBy('name')->pluck('name')->all()
        );

    }

    protected function assertWasAttached($objectName, $result)
    {
        $relationship = \Illuminate\Support\Str::plural($objectName);

        $this->assertInstanceOf('Laratrust\Tests\Models\User', $result);
        $this->assertEquals(1, $this->user->$relationship()->count());
        $this->user->$relationship()->sync([]);
    }

    protected function assertWasDetached($objectName, $result, $toReAttach)
    {
        $relationship = \Illuminate\Support\Str::plural($objectName);

        $this->assertInstanceOf('Laratrust\Tests\Models\User', $result);
        $this->assertEquals(0, $this->user->$relationship()->count());
        $this->user->$relationship()->attach($toReAttach->id);
    }
}
