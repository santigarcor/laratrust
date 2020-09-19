<?php

namespace Laratrust\Test;

use Mockery as m;
use Illuminate\Support\Str;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\OwnableObject;

class LaratrustUserTest extends LaratrustTestCase
{
    /**
     * @var User|null
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.teams.enabled', true);
    }

    public function testRolesRelationship()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->roles()
        );

        $this->app['config']->set('laratrust.teams.enabled', true);
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
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->permissions()
        );

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->permissions()
        );
    }

    public function testRolesTeams()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertNull($this->user->rolesTeams());

        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            $this->user->rolesTeams()
        );
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

    public function testMagicIsAbleToPermissionMethod()
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
        $this->assertTrue($this->user->isAbleToManageUser());

        $this->app['config']->set('laratrust.magic_can_method_case', 'snake_case');
        $this->assertTrue($this->user->isAbleToManageUser());

        $this->app['config']->set('laratrust.magic_can_method_case', 'camel_case');
        $this->assertTrue($this->user->isAbleToManageUser());
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

        $this->app['config']->set('laratrust.teams.enabled', false);
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

        $this->app['config']->set('laratrust.teams.enabled', false);
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

        $this->app['config']->set('laratrust.teams.enabled', false);
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

        $this->app['config']->set('laratrust.teams.enabled', false);
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

        $this->app['config']->set('laratrust.teams.enabled', false);
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

        $this->app['config']->set('laratrust.teams.enabled', false);
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
        $className = Str::snake(get_class($user)) . '_id';

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

    public function testUserIsAbleToAndOwnsaPostModel()
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
        $this->assertTrue($user->isAbleToAndOwns('edit-post', $post));
        $this->assertFalse($user->isAbleToAndOwns('update-post', $post));
        $this->assertFalse($user->isAbleToAndOwns('enhance-post', $post, [
            'requireAll' => true, 'foreignKeyName' => 'UserID'
        ]));
        $this->assertFalse($user->isAbleToAndOwns('edit-team', $post, [
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
        $roleB = Role::create(['name' => 'role_b']);
        $permissionA = Permission::create(['name' => 'permission_a']);
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

        $onlySomeColumns = $this->user->allPermissions(['name'])->first()->toArray();
        $this->assertArrayHasKey('id', $onlySomeColumns);
        $this->assertArrayHasKey('name', $onlySomeColumns);
        $this->assertArrayNotHasKey('displayName', $onlySomeColumns);
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
