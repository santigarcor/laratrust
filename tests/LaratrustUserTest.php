<?php

use Laratrust\Contracts\LaratrustUserInterface;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
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
        $belongsToMany = new stdClass();
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

        Config::shouldReceive('get')->once()->with('laratrust.role')
            ->andReturn('role_table_name');
        Config::shouldReceive('get')->once()->with('laratrust.role_user_table')
            ->andReturn('assigned_roles_table_name');
        Config::shouldReceive('get')->once()->with('laratrust.user_foreign_key')
            ->andReturn('user_id');
        Config::shouldReceive('get')->once()->with('laratrust.role_foreign_key')
            ->andReturn('role_id');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($belongsToMany, $user->roles());
    }

    public function testPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $belongsToMany = new stdClass();
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('belongsToMany')
            ->with('permission_table_name', 'assigned_permissions_table_name', 'user_id', 'permission_id')
            ->andReturn($belongsToMany)
            ->once();

        Config::shouldReceive('get')->once()->with('laratrust.permission')
            ->andReturn('permission_table_name');
        Config::shouldReceive('get')->once()->with('laratrust.permission_user_table')
            ->andReturn('assigned_permissions_table_name');
        Config::shouldReceive('get')->once()->with('laratrust.user_foreign_key')
            ->andReturn('user_id');
        Config::shouldReceive('get')->once()->with('laratrust.permission_foreign_key')
            ->andReturn('permission_id');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($belongsToMany, $user->permissions());
    }

    public function testHasRole()
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
        $this->assertTrue($user->hasRole([]));
        $this->assertTrue($user->hasRole('RoleA'));
        $this->assertTrue($user->hasRole('RoleB'));
        $this->assertFalse($user->hasRole('RoleC'));

        $this->assertTrue($user->hasRole(['RoleA', 'RoleB']));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC']));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], true));
        $this->assertFalse($user->hasRole(['RoleC', 'RoleD']));
    }

    public function testCan()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('manage_a');
        $permB = $this->mockPermission('manage_b');
        $permC = $this->mockPermission('manage_c');
        $permD = $this->mockPermission('manage_d');

        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];
        $user->permissions = [$permD];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(13)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(8)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(28)->andReturn('1440');
        Cache::shouldReceive('remember')
            ->with(
                "laratrust_permissions_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(15)->andReturn($user->permissions);
        Cache::shouldReceive('remember')
            ->with(
                "laratrust_roles_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(13)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->can([]));
        $this->assertTrue($user->can('manage_a'));
        $this->assertTrue($user->can('manage_b'));
        $this->assertTrue($user->can('manage_c'));
        $this->assertTrue($user->can('manage_d'));
        $this->assertFalse($user->can('manage_e'));

        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_c']));
        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_d']));
        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_d'], true));
        $this->assertFalse($user->can(['manage_a', 'manage_b', 'manage_e'], true));
        $this->assertFalse($user->can(['manage_e', 'manage_f']));
    }


    public function testCanWithPlaceholderSupport()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('admin.posts');
        $permB = $this->mockPermission('admin.pages');
        $permC = $this->mockPermission('admin.users');
        $permD = $this->mockPermission('config.things');

        $role = $this->mockRole('Role');

        $role->perms = [$permA, $permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$role];
        $user->permissions = [$permD];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $role->shouldReceive('cachedPermissions')->times(6)->andReturn($role->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(13)->andReturn('1440');
        Cache::shouldReceive('remember')
            ->with(
                "laratrust_permissions_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(7)->andReturn($user->permissions);
        Cache::shouldReceive('remember')
            ->with(
                "laratrust_roles_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(6)->andReturn($user->roles);


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
        $this->assertTrue($user->can(['config.*']));
        $this->assertFalse($user->can(['site.*']));
    }

    public function testAttachRole()
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
        $user->shouldReceive('attach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('attach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('attach')
            ->with(3)
            ->once()->ordered();

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

    public function testDetachRole()
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
        $user->shouldReceive('detach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(3)
            ->once()->ordered();

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

    public function testAttachRoles()
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
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(3)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->attachRoles([1, 2, 3]);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testDetachRoles()
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
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(3)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->detachRoles([1, 2, 3]);
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

        $relationship->shouldReceive('get')
                     ->andReturn($user->roles)->once();

        $user->shouldReceive('belongsToMany')
                    ->andReturn($relationship)->once();

        $user->shouldReceive('detachRole')->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->detachRoles();
    }

    public function testSyncRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $rolesIds = [1, 2, 3];
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('roles')
            ->andReturn($user);
        $user->shouldReceive('sync')
            ->with($rolesIds)
            ->once()->ordered();

        Cache::shouldReceive('forget')
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->syncRoles($rolesIds));
    }

    public function testAttachPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permissionObject = m::mock('Permission');
        $permissionArray = ['id' => 2];

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $permissionObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('permissions')
            ->andReturn($user);
        $user->shouldReceive('attach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('attach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('attach')
            ->with(3)
            ->once()->ordered();

        Cache::shouldReceive('forget')
            ->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->attachPermission($permissionObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->attachPermission($permissionArray);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->attachPermission(3);
        $this->assertInstanceOf('HasRoleUser', $result);
        $this->setExpectedException(InvalidArgumentException::class);
        $user->attachPermission(true);
    }

    public function testDetachPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permissionObject = m::mock('Permission');
        $permissionArray = ['id' => 2];

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $permissionObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('permissions')
            ->andReturn($user);
        $user->shouldReceive('detach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(3)
            ->once()->ordered();

        Cache::shouldReceive('forget')
            ->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->detachPermission($permissionObject);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->detachPermission($permissionArray);
        $this->assertInstanceOf('HasRoleUser', $result);
        $result = $user->detachPermission(3);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testAttachPermissions()
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
        $user->shouldReceive('attachPermission')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('attachPermission')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('attachPermission')
            ->with(3)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->attachPermissions([1, 2, 3]);
        $this->assertInstanceOf('HasRoleUser', $result);
    }

    public function testDetachPermissions()
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
        $user->shouldReceive('detachPermission')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detachPermission')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detachPermission')
            ->with(3)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $result = $user->detachPermissions([1, 2, 3]);
        $this->assertInstanceOf('HasRoleUser', $result);
    }


    public function testDetachAllPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permissionA = $this->mockRole('PermissionA');
        $permissionB = $this->mockRole('PermissionB');

        $user = m::mock('HasRoleUser')->makePartial();
        $user->permissions = [$permissionA, $permissionB];

        $relationship = m::mock('BelongsToMany');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.permission')->once()->andReturn('App\Permission');
        Config::shouldReceive('get')->with('laratrust.permission_user_table')->once()->andReturn('permission_user');
        Config::shouldReceive('get')->with('laratrust.user_foreign_key')->once()->andReturn('user_id');
        Config::shouldReceive('get')->with('laratrust.permission_foreign_key')->once()->andReturn('permission_id');

        $relationship->shouldReceive('get')
                     ->andReturn($user->permissions)->once();

        $user->shouldReceive('belongsToMany')
                    ->andReturn($relationship)->once();

        $user->shouldReceive('detachPermission')->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->detachPermissions();
    }

    public function testSyncPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permissionsIds = [1, 2, 3];
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('permissions')
            ->andReturn($user);
        $user->shouldReceive('sync')
            ->with($permissionsIds)
            ->once()->ordered();

        Cache::shouldReceive('forget')
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->syncPermissions($permissionsIds));
    }

    public function testUserOwnsaPostModel()
    {
        $user = m::mock('HasRoleUser')->makePartial();
        $className = snake_case(get_class($user)) . '_id';
        
        $post = new stdClass();
        $post->$className = $user->getKey();

        $post2 = new stdClass();
        $post2->$className = 9;

        $this->assertTrue($user->owns($post));
        $this->assertFalse($user->owns($post2));
    }

    public function testUserOwnsaPostModelCustomKey()
    {
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();
        $post->UserId = $user->getKey();

        $post2 = new stdClass();
        $post2->UserId = 9;

        $this->assertTrue($user->owns($post, 'UserId'));
        $this->assertFalse($user->owns($post2, 'UserId'));
    }

    public function testScopeWhereRoleIs()
    {
        $query = m::mock();

        $query->shouldReceive('whereHas')
            ->with('roles', m::any())
            ->once()
            ->andReturn($query);

        $user = m::mock('HasRoleUser')->makePartial();

        $this->assertInstanceOf(get_class($query), $user->scopeWhereRoleIs($query, 'admin'));
    }
}

class HasRoleUser extends Model implements LaratrustUserInterface
{
    use LaratrustUserTrait;

    public $roles;
    public $permissions;
    public $primaryKey;

    public function __construct()
    {
        $this->primaryKey = 'id';
        $this->setAttribute('id', 4);
    }

    public function getKey()
    {
        return $this->id;
    }

    public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
    }
}
