<?php

namespace Laratrust\Test;

use Mockery as m;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Illuminate\Support\Facades\Config;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\OwnableObject;

class LaratrustUserTest extends LaratrustTestCase
{
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.use_teams', true);
    }

    public function testRolesRelationship()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->roles()
        );

        $this->app['config']->set('laratrust.use_teams', true);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->roles()
        );
    }

    public function testPermissionsRelationship()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->permissions()
        );

        $this->app['config']->set('laratrust.use_teams', true);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->permissions()
        );
    }

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
        $roles = [
            Role::create(['name' => 'role_a'])->id => ['team_id' => null],
            Role::create(['name' => 'role_b'])->id => ['team_id' => null],
            Role::create(['name' => 'role_c'])->id => ['team_id' => $team->id ]
        ];
        $this->app['config']->set('laratrust.use_teams', true);
        $this->user->roles()->attach($roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasRole([]));
        $this->assertTrue($this->user->hasRole('role_a'));
        $this->assertTrue($this->user->hasRole('role_b'));
        $this->assertTrue($this->user->hasRole('role_c'));
        $this->app['config']->set('laratrust.teams_strict_check', true);
        $this->assertFalse($this->user->hasRole('role_c'));
        $this->app['config']->set('laratrust.teams_strict_check', false);
        $this->assertTrue($this->user->hasRole('role_c', 'team_a'));
        $this->assertFalse($this->user->hasRole('role_a', 'team_a'));

        $this->assertTrue($this->user->hasRole('role_a|role_b'));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_b']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertFalse($this->user->hasRole(['role_a', 'role_c'], 'team_a', true));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], true));
        $this->assertFalse($this->user->hasRole(['role_c', 'role_d'], true));

        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertFalse($this->user->hasRole(['role_c', 'role_d'], true));
    }

    public function testHasPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);

        $roleA = Role::create(['name' => 'role_a'])
            ->attachPermission(Permission::create(['name' => 'permission_a']));
        $roleB = Role::create(['name' => 'role_b'])
            ->attachPermission(Permission::create(['name' => 'permission_b']));

        $this->user->roles()->attach([
            $roleA->id => ['team_id' => null],
            $roleB->id => ['team_id' => $team->id ]
        ]);

        $this->user->permissions()->attach([
            Permission::create(['name' => 'permission_c'])->id => ['team_id' => $team->id ],
            Permission::create(['name' => 'permission_d'])->id => ['team_id' => null],
        ]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasPermission([]));
        $this->assertTrue($this->user->hasPermission('permission_a'));
        $this->assertTrue($this->user->hasPermission('permission_b', 'team_a'));
        $this->assertTrue($this->user->hasPermission('permission_b', $team));
        $this->app['config']->set('laratrust.teams_strict_check', true);
        $this->assertFalse($this->user->hasPermission('permission_c'));
        $this->app['config']->set('laratrust.teams_strict_check', false);
        $this->assertTrue($this->user->hasPermission('permission_c'));
        $this->assertTrue($this->user->hasPermission('permission_c', 'team_a'));
        $this->assertTrue($this->user->hasPermission('permission_c', $team));
        $this->assertTrue($this->user->hasPermission('permission_d'));
        $this->assertFalse($this->user->hasPermission('permission_e'));

        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_c', 'permission_d', 'permission_e']));
        $this->assertTrue($this->user->hasPermission('permission_a|permission_b|permission_c|permission_d|permission_e'));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_d'], true));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], true));
        $this->assertFalse($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], 'team_a', true));
        $this->assertFalse($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], $team, true));
        $this->assertFalse($this->user->hasPermission(['permission_a', 'permission_b', 'permission_e'], true));
        $this->assertFalse($this->user->hasPermission(['permission_e', 'permission_f']));

        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], 'team_a', true));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], $team, true));
    }

    public function testCan()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

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
        $this->assertTrue($user->can('manage_user'));
    }

    public function testIsAbleTo()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

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

    public function testHasPermissionWithPlaceholderSupport()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);

        $role = Role::create(['name' => 'role_a'])
            ->attachPermissions([
                Permission::create(['name' => 'admin.posts']),
                Permission::create(['name' => 'admin.pages']),
                Permission::create(['name' => 'admin.users']),
            ]);

        $this->user->roles()->attach($role->id);

        $this->user->permissions()->attach([
            Permission::create(['name' => 'config.things'])->id => ['team_id' => $team->id ],
            Permission::create(['name' => 'config.another_things'])->id => ['team_id' => $team->id],
        ]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasPermission('admin.posts'));
        $this->assertTrue($this->user->hasPermission('admin.pages'));
        $this->assertTrue($this->user->hasPermission('admin.users'));
        $this->assertFalse($this->user->hasPermission('admin.config', 'team_a'));

        $this->assertTrue($this->user->hasPermission(['admin.*']));
        $this->assertTrue($this->user->hasPermission(['admin.*']));
        $this->assertTrue($this->user->hasPermission(['config.*'], 'team_a'));
        $this->assertTrue($this->user->hasPermission(['config.*']));
        $this->assertFalse($this->user->hasPermission(['site.*']));
    }

    public function testMagicCanPermissionMethod()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $this->user->permissions()->attach([
            Permission::create(['name' => 'manage-user'])->id,
            Permission::create(['name' => 'manage_user'])->id,
            Permission::create(['name' => 'manageUser'])->id,
        ]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->app['config']->set('laratrust.magic_can_method_case', 'kebab_case');
        $this->assertTrue($this->user->canManageUser());

        $this->app['config']->set('laratrust.magic_can_method_case', 'snake_case');
        $this->assertTrue($this->user->canManageUser());

        $this->app['config']->set('laratrust.magic_can_method_case', 'camel_case');
        $this->assertTrue($this->user->canManageUser());
    }

    public function testAttachRole()
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
        $this->assertWasAttached('role', $this->user->attachRole($role));
        // Can attach role by passing an id
        $this->assertWasAttached('role', $this->user->attachRole($role->id));
        // Can attach role by passing an array with 'id' => $id
        $this->assertWasAttached('role', $this->user->attachRole($role->toArray()));
        // Can attach role by passing the role name
        $this->assertWasAttached('role', $this->user->attachRole('role_a'));
        // Can attach role by passing the role and team
        $this->assertWasAttached('role', $this->user->attachRole($role, $team));
        // Can attach role by passing the role and team id
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->attachRole($role, $team->id));
        $this->assertEquals($team->id, $this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);
        // Can attach role by passing the role and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->attachRole($role, 'team_a'));
        $this->assertEquals($team->id, $this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);

        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertWasAttached('role', $this->user->attachRole($role));

        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->attachRole($role, 'team_a'));
        $this->assertNull($this->user->roles()->first()->pivot->team_id);
        $this->user->roles()->sync([]);
    }

    public function testDetachRole()
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
        $this->assertWasDetached('role', $this->user->detachRole($role), $role);
        // Can detach role by passing an id
        $this->assertWasDetached('role', $this->user->detachRole($role->id), $role);
        // Can detach role by passing an array with 'id' => $id
        $this->assertWasDetached('role', $this->user->detachRole($role->toArray()), $role);
        // Can detach role by passing the role name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachRole('role_a'));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachRole($role, $team));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team id
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachRole($role, $team->id));
        $this->assertEquals(0, $this->user->roles()->count());
        $this->user->roles()->attach($role->id, ['team_id' => $team->id]);
        // Can detach role by passing the role and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachRole($role, 'team_a'));

        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertWasDetached('role', $this->user->detachRole($role), $role);
        $this->assertWasDetached('role', $this->user->detachRole($role, 'TeamA'), $role);
    }

    public function testAttachRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('attachRole')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->attachRoles([1, 2, 3]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->attachRoles([1, 2, 3], 'TeamA'));
    }

    public function testDetachRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('roles->get')->andReturn([1, 2, 3])->once();
        $user->shouldReceive('detachRole')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(9);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->detachRoles([1, 2, 3]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->detachRoles([]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->detachRoles([1, 2, 3], 'TeamA'));
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
        $this->user->attachRole(Role::create(['name' => 'role_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRoles($roles));
        $this->assertEquals(2, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRoles($roles, 'team_a'));
        $this->assertEquals(4, $this->user->roles()->count());

        $this->app['config']->set('laratrust.use_teams', false);
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
        $this->user->attachRole(Role::create(['name' => 'role_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles));
        $this->assertEquals(3, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles, 'team_a'));
        $this->assertEquals(5, $this->user->roles()->count());

        $this->app['config']->set('laratrust.use_teams', false);
        $this->user->detachRoles([1]);
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles, null));
        $this->assertEquals(4, $this->user->roles()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncRolesWithoutDetaching($roles, 'team_a', false));
        $this->assertEquals(4, $this->user->roles()->count());
    }

    public function testAttachPermission()
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
        $this->assertWasAttached('permission', $this->user->attachPermission($permission));
        // Can attach permission by passing an id
        $this->assertWasAttached('permission', $this->user->attachPermission($permission->id));
        // Can attach permission by passing an array with 'id' => $id
        $this->assertWasAttached('permission', $this->user->attachPermission($permission->toArray()));
        // Can attach permission by passing the permission name
        $this->assertWasAttached('permission', $this->user->attachPermission('permission_a'));
        // Can attach permission by passing the permission and team
        $this->assertWasAttached('permission', $this->user->attachPermission($permission, $team));
        // Can attach permission by passing the permission and team id
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->attachPermission($permission, $team->id));
        $this->assertEquals($team->id, $this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);
        // Can attach permission by passing the permission and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->attachPermission($permission, 'team_a'));
        $this->assertEquals($team->id, $this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);

        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertWasAttached('permission', $this->user->attachPermission($permission));

        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->attachPermission($permission, 'team_a'));
        $this->assertNull($this->user->permissions()->first()->pivot->team_id);
        $this->user->permissions()->sync([]);
    }

    public function testDetachPermission()
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
        $this->assertWasDetached('permission', $this->user->detachPermission($permission), $permission);
        // Can detach permission by passing an id
        $this->assertWasDetached('permission', $this->user->detachPermission($permission->id), $permission);
        // Can detach permission by passing an array with 'id' => $id
        $this->assertWasDetached('permission', $this->user->detachPermission($permission->toArray()), $permission);
        // Can detach permission by passing the permission name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachPermission('permission_a'));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachPermission($permission, $team));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team id
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachPermission($permission, $team->id));
        $this->assertEquals(0, $this->user->permissions()->count());
        $this->user->permissions()->attach($permission->id, ['team_id' => $team->id]);
        // Can detach permission by passing the permission and team name
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->detachPermission($permission, 'team_a'));

        $this->app['config']->set('laratrust.use_teams', false);
        $this->assertWasDetached('permission', $this->user->detachPermission($permission), $permission);
        $this->assertWasDetached('permission', $this->user->detachPermission($permission, 'team_a'), $permission);
    }

    public function testAttachPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('attachPermission')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->attachPermissions([1, 2, 3]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->attachPermissions([1, 2, 3], 'TeamA'));
    }

    public function testDetachPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('permissions->get')->andReturn([1, 2, 3])->once();
        $user->shouldReceive('detachPermission')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(9);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->detachPermissions([1, 2, 3]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->detachPermissions([]));
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $user->detachPermissions([1, 2, 3], 'TeamA'));
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
        $this->user->attachPermission(Permission::create(['name' => 'permission_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissions($permissions));
        $this->assertEquals(2, $this->user->permissions()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissions($permissions, 'team_a'));
        $this->assertEquals(4, $this->user->permissions()->count());

        $this->app['config']->set('laratrust.use_teams', false);
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
        $this->user->attachPermission(Permission::create(['name' => 'permission_c']));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions));
        $this->assertEquals(3, $this->user->permissions()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions, 'team_a'));
        $this->assertEquals(5, $this->user->permissions()->count());

        $this->app['config']->set('laratrust.use_teams', false);
        $this->user->detachPermissions([1]);
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions, null));
        $this->assertEquals(4, $this->user->permissions()->count());
        $this->assertInstanceOf('Laratrust\Tests\Models\User', $this->user->syncPermissionsWithoutDetaching($permissions, 'team_a', false));
        $this->assertEquals(4, $this->user->permissions()->count());
    }

    public function testUserOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $className = snake_case(get_class($user)) . '_id';

        $post = new \stdClass();
        $post->$className = $user->getKey();

        $post2 = new \stdClass();
        $post2->$className = 9;

        $ownableObject = new OwnableObject;

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->owns($post));
        $this->assertFalse($user->owns($post2));
        $this->assertFalse($user->owns($ownableObject));
    }

    public function testUserOwnsaPostModelCustomKey()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $post = new \stdClass();
        $post->UserId = $user->getKey();

        $post2 = new \stdClass();
        $post2->UserId = 9;

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->owns($post, 'UserId'));
        $this->assertFalse($user->owns($post2, 'UserId'));
    }

    public function testUserHasRoleAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $post = new \stdClass();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasRole')->with('editor', null, false)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($post, null)->andReturn(true)->once();
        $user->shouldReceive('hasRole')->with('regular-user', null, false)->andReturn(false)->once();
        $user->shouldReceive('hasRole')->with('administrator', null, true)->andReturn(true)->once();
        $user->shouldReceive('hasRole')->with('team-member', $team, true)->andReturn(false)->once();
        $user->shouldReceive('owns')->with($post, 'UserID')->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasRoleAndOwns('editor', $post));
        $this->assertFalse($user->hasRoleAndOwns('regular-user', $post));
        $this->assertFalse($user->hasRoleAndOwns('administrator', $post, [
            'requireAll' => true, 'foreignKeyName' => 'UserID'
        ]));
        $this->assertFalse($user->hasRoleAndOwns('team-member', $post, [
            'requireAll' => true,
            'foreignKeyName' => 'UserID',
            'team' => $team
        ]));
    }

    public function testUserCanAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $post = new \stdClass();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasPermission')->with('edit-post', null, false)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($post, null)->andReturn(true)->once();
        $user->shouldReceive('hasPermission')->with('update-post', null, false)->andReturn(false)->once();
        $user->shouldReceive('hasPermission')->with('enhance-post', null, true)->andReturn(true)->once();
        $user->shouldReceive('hasPermission')->with('edit-team', $team, true)->andReturn(false)->once();
        $user->shouldReceive('owns')->with($post, 'UserID')->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->canAndOwns('edit-post', $post));
        $this->assertFalse($user->canAndOwns('update-post', $post));
        $this->assertFalse($user->canAndOwns('enhance-post', $post, [
            'requireAll' => true, 'foreignKeyName' => 'UserID'
        ]));
        $this->assertFalse($user->canAndOwns('edit-team', $post, [
            'requireAll' => true,
            'foreignKeyName' => 'UserID',
            'team' => $team
        ]));
    }

    public function testAllPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleA = Role::create(['name' => 'role_a']);
        $roleB =  Role::create(['name' => 'role_b']);
        $permissionA =  Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);

        $roleA->attachPermissions([$permissionA, $permissionB]);
        $roleB->attachPermissions([$permissionB, $permissionC]);
        $this->user->attachPermissions([$permissionB, $permissionC]);
        $this->user->attachRoles([$roleA, $roleB]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            ['permission_a', 'permission_b', 'permission_c'],
            $this->user->allPermissions()->sortBy('name')->pluck('name')->all()
        );
    }

    public function testScopeWhereRoleIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $query = m::mock();
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $query->shouldReceive('whereHas')
            ->with('roles', m::any())
            ->once()
            ->andReturn($query);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(get_class($query), $user->scopeWhereRoleIs($query, 'admin'));
    }

    public function testScopeWherePermissionIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $query = m::mock();
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $query->shouldReceive('whereHas')
            ->with('roles.permissions', m::any())
            ->once()
            ->andReturn($query);

        $query->shouldReceive('orWhereHas')
            ->with('permissions', m::any())
            ->once()
            ->andReturn($query);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(get_class($query), $user->scopeWherePermissionIs($query, 'create-users'));
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
