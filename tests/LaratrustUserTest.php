<?php

use Laratrust\Contracts\LaratrustUserInterface;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Laratrust\Permission;
use Laratrust\Role;
use Mockery as m;

class LaratrustUserTest extends UserTest
{

    public function testRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $belongsToMany = m::mock(new stdClass());
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('belongsToMany')
            ->with('role_table_name', 'assigned_roles_table_name', 'user_id', 'role_id')
            ->andReturn($belongsToMany)
            ->once();

        $belongsToMany->shouldReceive('withPivot')
            ->with('group_id')
            ->andReturn($belongsToMany)
            ->once();

        Config::shouldReceive('get')->once()->with('laratrust.role')
            ->andReturn('role_table_name');
        Config::shouldReceive('get')->once()->with('laratrust.role_user_table')
            ->andReturn('assigned_roles_table_name');
        Config::shouldReceive('get')->once()->with('laratrust.user_foreign_key')
            ->andReturn('user_id');
        Config::shouldReceive('get')->once()->with('laratrust.role_foreign_key')
            ->andReturn('role_id');
        Config::shouldReceive('get')->once()->with('laratrust.group_foreign_key')
            ->andReturn('group_id');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($belongsToMany, $user->roles());
    }

    public function testHasRoleWithoutGroup()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(9)->andReturn('1440');
        Cache::shouldReceive('remember')->times(9)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasRole('RoleA'));
        $this->assertTrue($user->hasRole('RoleB'));
        $this->assertFalse($user->hasRole('RoleC'));

        $this->assertTrue($user->hasRole(['RoleA', 'RoleB']));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC']));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], true));
        $this->assertFalse($user->hasRole(['RoleC', 'RoleD']));
    }

    public function testHasRoleWithGroup()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $group = $this->mockGroup('GroupA');
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB', $group->id);

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.group')->times(10)->andReturn($group);
        $group->shouldReceive('where')->with('name', 'GroupA')->times(10)->andReturn($group);
        $group->shouldReceive('first')->times(10)->andReturn($group);
        $group->shouldReceive('getKey')->times(10)->andReturn($group->id);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(10)->andReturn('1440');
        Cache::shouldReceive('remember')->times(10)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertFalse($user->hasRole('RoleA', $group->name));
        $this->assertTrue($user->hasRole('RoleB', $group->name));
        $this->assertFalse($user->hasRole('RoleC', $group->name));

        $this->assertTrue($user->hasRole(['RoleA', 'RoleB'], $group->name));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], $group->name));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleB'], $group->name, true));
        $this->assertFalse($user->hasRole(['RoleC', 'RoleD'], $group->name));
    }

    public function testCanWithoutGroups()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('manage_a');
        $permB = $this->mockPermission('manage_b');
        $permC = $this->mockPermission('manage_c');

        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(11)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(7)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(22)->andReturn('1440');
        Cache::shouldReceive('remember')->times(22)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->can('manage_a'));
        $this->assertTrue($user->can('manage_b'));
        $this->assertTrue($user->can('manage_c'));
        $this->assertFalse($user->can('manage_d'));

        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_c']));
        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_d']));
        $this->assertFalse($user->can(['manage_a', 'manage_b', 'manage_d'], true));
        $this->assertFalse($user->can(['manage_d', 'manage_e']));
    }

    public function testCanWithGroups()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('manage_a');
        $permB = $this->mockPermission('manage_b');
        $permC = $this->mockPermission('manage_c');

        $group = $this->mockGroup('GroupA');
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB', $group->id);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.group')->times(11)->andReturn($group);
        $group->shouldReceive('where')->with('name', 'GroupA')->times(11)->andReturn($group);
        $group->shouldReceive('first')->times(11)->andReturn($group);
        $group->shouldReceive('getKey')->times(11)->andReturn($group->id);

        $roleA->shouldReceive('cachedPermissions')->times(0);
        $roleB->shouldReceive('cachedPermissions')->times(11)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(22)->andReturn('1440');
        Cache::shouldReceive('remember')->times(22)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertFalse($user->can('manage_a', $group->name));
        $this->assertTrue($user->can('manage_b', $group->name));
        $this->assertTrue($user->can('manage_c', $group->name));
        $this->assertFalse($user->can('manage_d', $group->name));

        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_c'], $group->name));
        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_d'], $group->name));
        $this->assertFalse($user->can(['manage_a', 'manage_b', 'manage_d'], $group->name, true));
        $this->assertFalse($user->can(['manage_d', 'manage_e'], $group->name));
    }

    public function testCanWithPlaceholderSupportWithoutGroups ()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('admin.posts');
        $permB = $this->mockPermission('admin.pages');
        $permC = $this->mockPermission('admin.users');

        $role = $this->mockRole('Role');

        $role->perms = [$permA, $permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$role];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $role->shouldReceive('cachedPermissions')->times(6)->andReturn($role->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(12)->andReturn('1440');
        Cache::shouldReceive('remember')->times(12)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->can('admin.posts'));
        $this->assertTrue($user->can('admin.pages'));
        $this->assertTrue($user->can('admin.users'));
        $this->assertFalse($user->can('admin.config'));

        $this->assertTrue($user->can(['admin.*']));
        $this->assertFalse($user->can(['site.*']));
    }

    public function testCanWithPlaceholderSupportWithGroups ()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('admin.posts');
        $permB = $this->mockPermission('admin.pages');
        $permC = $this->mockPermission('admin.users');

        $group = $this->mockGroup('GroupA');
        $role = $this->mockRole('Role', $group->id);

        $role->perms = [$permA, $permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$role];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.group')->times(6)->andReturn($group);
        $group->shouldReceive('where')->with('name', 'GroupA')->times(6)->andReturn($group);
        $group->shouldReceive('first')->times(6)->andReturn($group);
        $group->shouldReceive('getKey')->times(6)->andReturn($group->id);

        $role->shouldReceive('cachedPermissions')->times(6)->andReturn($role->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(12)->andReturn('1440');
        Cache::shouldReceive('remember')->times(12)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->can('admin.posts', $group->name));
        $this->assertTrue($user->can('admin.pages', $group->name));
        $this->assertTrue($user->can('admin.users', $group->name));
        $this->assertFalse($user->can('admin.config', $group->name));

        $this->assertTrue($user->can(['admin.*'], $group->name));
        $this->assertFalse($user->can(['site.*'], $group->name));
    }


    public function testAttachRoleWithoutGroup()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleObject = m::mock('Role');
        $roleArray = ['id' => 2];

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('roles')->andReturn($user);
        $user->shouldReceive('wherePivot')
            ->with('group_id', null)
            ->andReturn($user)
            ->times(3);

        $user->shouldReceive('detach')->with(1)->once();
        $user->shouldReceive('detach')->with(2)->once();
        $user->shouldReceive('detach')->with(3)->once();

        $user->shouldReceive('attach')->with(1, ['group_id' => null])->once()->ordered();
        $user->shouldReceive('attach')->with(2, ['group_id' => null])->once()->ordered();
        $user->shouldReceive('attach')->with(3, ['group_id' => null])->once()->ordered();
        
        Config::shouldReceive('get')->with('laratrust.group_foreign_key')
            ->andReturn('group_id')
            ->times(6);
        Cache::shouldReceive('forget')
            ->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->attachRole($roleObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->attachRole($roleArray);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->attachRole(3);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testAttachRoleWithGroup()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleObject = m::mock('Role');
        $roleArray = ['id' => 2];
        $groupObject = m::mock('Group');

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleObject->shouldReceive('getKey')
            ->andReturn(1);

        $groupObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('roles')->andReturn($user);
        $user->shouldReceive('wherePivot')
            ->with('group_id', 1)
            ->andReturn($user)
            ->times(3);

        $user->shouldReceive('detach')->with(1)->once();
        $user->shouldReceive('detach')->with(2)->once();
        $user->shouldReceive('detach')->with(3)->once();

        $user->shouldReceive('attach')->with(1, ['group_id' => 1])->once()->ordered();
        $user->shouldReceive('attach')->with(2, ['group_id' => 1])->once()->ordered();
        $user->shouldReceive('attach')->with(3, ['group_id' => 1])->once()->ordered();
        
        Config::shouldReceive('get')->with('laratrust.group_foreign_key')
            ->andReturn('group_id')
            ->times(6);
        Cache::shouldReceive('forget')
            ->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->attachRole($roleObject, $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->attachRole($roleArray, $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->attachRole(3, $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testDetachRoleWithoutGroup()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleObject = m::mock('Role');
        $roleArray = ['id' => 2];

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('roles')
            ->andReturn($user);

        $user->shouldReceive('wherePivot')
            ->with('group_id', null)
            ->andReturn($user)
            ->times(3);

        $user->shouldReceive('detach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(3)
            ->once()->ordered();

        Config::shouldReceive('get')->with('laratrust.group_foreign_key')
            ->andReturn('group_id')
            ->times(3);
        Cache::shouldReceive('forget')
            ->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->detachRole($roleObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->detachRole($roleArray);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->detachRole(3);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testDetachRoleWithGroup()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleObject = m::mock('Role');
        $groupObject = m::mock('Group');
        $roleArray = ['id' => 2];

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleObject->shouldReceive('getKey')
            ->andReturn(1);
        $groupObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('roles')
            ->andReturn($user);

        $user->shouldReceive('wherePivot')
            ->with('group_id', 1)
            ->andReturn($user)
            ->times(3);

        $user->shouldReceive('detach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(3)
            ->once()->ordered();

        Config::shouldReceive('get')->with('laratrust.group_foreign_key')
            ->andReturn('group_id')
            ->times(3);
        Cache::shouldReceive('forget')
            ->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->detachRole($roleObject, $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->detachRole($roleArray, $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->detachRole(3, $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testAttachRolesWithoutGroups()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('attachRole')
            ->with(1, null)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(2, null)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(3, null)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->attachRoles([1, 2, 3]);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testAttachRolesWithGroups()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();
        $groupObject = m::mock('Group');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('attachRole')
            ->with(1, $groupObject)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(2, $groupObject)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(3, $groupObject)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->attachRoles([1, 2, 3], $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testDetachRolesWithoutGroups()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('detachRole')
            ->with(1, null)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(2, null)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(3, null)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->detachRoles([1, 2, 3]);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testDetachRolesWithGroups()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();
        $groupObject = m::mock('Group');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('detachRole')
            ->with(1, $groupObject)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(2, $groupObject)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(3, $groupObject)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->detachRoles([1, 2, 3], $groupObject);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testDetachAllRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];

        $relationship = m::mock('BelongsToMany');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.role')->once()->andReturn('App\Role');
        Config::shouldReceive('get')->with('laratrust.role_user_table')->once()->andReturn('role_user');
        Config::shouldReceive('get')->with('laratrust.user_foreign_key')->once()->andReturn('user_id');
        Config::shouldReceive('get')->with('laratrust.role_foreign_key')->once()->andReturn('role_id');
        Config::shouldReceive('get')->with('laratrust.group_foreign_key')->once()->andReturn('group_id');

        $relationship->shouldReceive('get')
                     ->andReturn($user->roles)->once();

        $user->shouldReceive('belongsToMany')
                    ->andReturn($relationship)->once();
        $relationship->shouldReceive('withPivot')
                     ->andReturn($relationship)->once();

        $user->shouldReceive('detachRole')->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->detachRoles();

    }

    function testUserOwnsaPostModel()
    {
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();
        $post->mockery_13__has_role_user_id = $user->getKey();

        $post2 = new stdClass();
        $post2->mockery_13__has_role_user_id = 9;

        $this->assertTrue($user->owns($post));
        $this->assertFalse($user->owns($post2));
    }
}