<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\LaratrustUserInterface;
use Laratrust\Contracts\Ownable;
use Laratrust\Traits\LaratrustUserTrait;
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
        $morphToMany = m::mock(new stdClass());
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('morphToMany')
            ->with('role', 'user', 'assigned_roles_table_name', 'user_id', 'role_id')
            ->andReturn($morphToMany)
            ->once();
        $morphToMany->shouldReceive('withPivot')
            ->with('group_id')
            ->andReturn($morphToMany);

        Config::shouldReceive('get')->once()->with('laratrust.role')
            ->andReturn('role');
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
        $this->assertSame($morphToMany, $user->roles());
    }

    public function testPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $morphToMany = m::mock(new stdClass());
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('morphToMany')
            ->with('permission', 'user', 'assigned_permissions_table_name', 'user_id', 'permission_id')
            ->andReturn($morphToMany)
            ->once();
        $morphToMany->shouldReceive('withPivot')
            ->with('group_id')
            ->andReturn($morphToMany);

        Config::shouldReceive('get')->once()->with('laratrust.permission')->andReturn('permission');
        Config::shouldReceive('get')->once()->with('laratrust.permission_user_table')
            ->andReturn('assigned_permissions_table_name');
        Config::shouldReceive('get')->once()->with('laratrust.user_foreign_key')->andReturn('user_id');
        Config::shouldReceive('get')->once()->with('laratrust.permission_foreign_key')->andReturn('permission_id');
        Config::shouldReceive('get')->once()->with('laratrust.group_foreign_key')
            ->andReturn('group_id');
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($morphToMany, $user->permissions());
    }

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $group = $this->mockGroup('GroupA');
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');
        $roleC = $this->mockRole('RoleC', $group->id);

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB, $roleC];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(14)->andReturn('1440');
        Cache::shouldReceive('remember')->times(14)->andReturn($user->roles);
        Config::shouldReceive('get')->with('laratrust.group_foreign_key')
            ->times(21)
            ->andReturn('group_id');
        Config::shouldReceive('get')->with('laratrust.group')
            ->times(5)
            ->andReturn($group);
        $group->shouldReceive('where')->with('name', 'GroupA')->times(5)->andReturn($group);
        $group->shouldReceive('first')->times(5)->andReturn($group);
        $group->shouldReceive('getKey')->times(5)->andReturn($group->id);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasRole([]));
        $this->assertTrue($user->hasRole('RoleA'));
        $this->assertTrue($user->hasRole('RoleB'));
        $this->assertFalse($user->hasRole('RoleC'));
        $this->assertTrue($user->hasRole('RoleC', 'GroupA'));
        $this->assertFalse($user->hasRole('RoleA', 'GroupA'));

        $this->assertTrue($user->hasRole(['RoleA', 'RoleB']));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC']));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC'], 'GroupA'));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], 'GroupA', true));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], true));
        $this->assertFalse($user->hasRole(['RoleC', 'RoleD']));
    }

    public function testHasPermission()
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
        $this->assertTrue($user->hasPermission([]));
        $this->assertTrue($user->hasPermission('manage_a'));
        $this->assertTrue($user->hasPermission('manage_b'));
        $this->assertTrue($user->hasPermission('manage_c'));
        $this->assertTrue($user->hasPermission('manage_d'));
        $this->assertFalse($user->hasPermission('manage_e'));

        $this->assertTrue($user->hasPermission(['manage_a', 'manage_b', 'manage_c']));
        $this->assertTrue($user->hasPermission(['manage_a', 'manage_b', 'manage_d']));
        $this->assertTrue($user->hasPermission(['manage_a', 'manage_b', 'manage_d'], true));
        $this->assertFalse($user->hasPermission(['manage_a', 'manage_b', 'manage_e'], true));
        $this->assertFalse($user->hasPermission(['manage_e', 'manage_f']));
    }

    public function testIsAbleTo()
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
        $user->shouldReceive('hasPermission')->with('manage_user', false)->andReturn(true);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->isAbleTo('manage_user'));
        // $this->assertTrue($user->isAbleTo('manage_a'));
        // $this->assertTrue($user->isAbleTo('manage_b'));
        // $this->assertTrue($user->isAbleTo('manage_c'));
        // $this->assertTrue($user->isAbleTo('manage_d'));
        // $this->assertFalse($user->isAbleTo('manage_e'));

        // $this->assertTrue($user->isAbleTo(['manage_a', 'manage_b', 'manage_c']));
        // $this->assertTrue($user->isAbleTo(['manage_a', 'manage_b', 'manage_d']));
        // $this->assertTrue($user->isAbleTo(['manage_a', 'manage_b', 'manage_d'], true));
        // $this->assertFalse($user->isAbleTo(['manage_a', 'manage_b', 'manage_e'], true));
        // $this->assertFalse($user->isAbleTo(['manage_e', 'manage_f']));
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
        $roleObject->shouldReceive('getKey')->andReturn(1)->twice();
        $user->shouldReceive('roles')->andReturn($user);
        $user->shouldReceive('attach')->with(1)->once()->ordered();
        $user->shouldReceive('attach')->with(2)->once()->ordered();
        $user->shouldReceive('attach')->with(3)->once()->ordered();
        $user->shouldReceive('attach')->with(1)->once()->ordered();
        Cache::shouldReceive('forget')->times(8);
        Config::shouldReceive('get')->with('laratrust.role')->andReturn($roleObject)->once();
        $roleObject->shouldReceive('where')->with('name', 'admin')->andReturn($roleObject);
        $roleObject->shouldReceive('firstOrFail')->andReturn($roleObject);

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
        $result = $user->attachRole('admin');
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
        $roleObject->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('roles')->andReturn($user);
        $user->shouldReceive('detach')->with(1)->once()->ordered();
        $user->shouldReceive('detach')->with(2)->once()->ordered();
        $user->shouldReceive('detach')->with(3)->once()->ordered();
        Cache::shouldReceive('forget')->times(6);

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
        $user->shouldReceive('attachRole')->with(1)->once()->ordered();
        $user->shouldReceive('attachRole')->with(2)->once()->ordered();
        $user->shouldReceive('attachRole')->with(3)->once()->ordered();

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
        $user->shouldReceive('detachRole')->with(1)->once()->ordered();
        $user->shouldReceive('detachRole')->with(2)->once()->ordered();
        $user->shouldReceive('detachRole')->with(3)->once()->ordered();

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

        $relationship = m::mock('MorphToMany');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.role')->once()->andReturn('App\Role');
        Config::shouldReceive('get')->with('laratrust.role_user_table')->once()->andReturn('role_user');
        Config::shouldReceive('get')->with('laratrust.user_foreign_key')->once()->andReturn('user_id');
        Config::shouldReceive('get')->with('laratrust.role_foreign_key')->once()->andReturn('role_id');
        Config::shouldReceive('get')->with('laratrust.group_foreign_key')
            ->once()->andReturn('group_id');
        $user->shouldReceive('morphToMany')->andReturn($relationship)->once();
        $relationship->shouldReceive('withPivot')->with('group_id')->andReturn($relationship)->once();
        $relationship->shouldReceive('get')->andReturn($user->roles)->once();
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
        $user->shouldReceive('roles')->andReturn($user);
        $user->shouldReceive('sync')->with($rolesIds)->once()->ordered();
        Cache::shouldReceive('forget')->twice();

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
        $permissionObject->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('permissions')->andReturn($user);
        $user->shouldReceive('attach')->with(1)->once()->ordered();
        $user->shouldReceive('attach')->with(2)->once()->ordered();
        $user->shouldReceive('attach')->with(3)->once()->ordered();
        $user->shouldReceive('attach')->with(1)->once()->ordered();
        Cache::shouldReceive('forget')->times(8);
        Config::shouldReceive('get')->with('laratrust.permission')->andReturn($permissionObject)->once();
        $permissionObject->shouldReceive('where')->with('name', 'edit-post')->andReturn($permissionObject);
        $permissionObject->shouldReceive('firstOrFail')->andReturn($permissionObject);

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
        $result = $user->attachPermission('edit-post');
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
        $permissionObject->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('permissions')->andReturn($user);
        $user->shouldReceive('detach')->with(1)->once()->ordered();
        $user->shouldReceive('detach')->with(2)->once()->ordered();
        $user->shouldReceive('detach')->with(3)->once()->ordered();
        Cache::shouldReceive('forget')->times(6);

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
        $user->shouldReceive('attachPermission')->with(1)->once()->ordered();
        $user->shouldReceive('attachPermission')->with(2)->once()->ordered();
        $user->shouldReceive('attachPermission')->with(3)->once()->ordered();

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
        $user->shouldReceive('detachPermission')->with(1)->once()->ordered();
        $user->shouldReceive('detachPermission')->with(2)->once()->ordered();
        $user->shouldReceive('detachPermission')->with(3)->once()->ordered();

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

        $relationship = m::mock('MorphToMany');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.permission')->once()->andReturn('App\Permission');
        Config::shouldReceive('get')->with('laratrust.permission_user_table')->once()->andReturn('permission_user');
        Config::shouldReceive('get')->with('laratrust.user_foreign_key')->once()->andReturn('user_id');
        Config::shouldReceive('get')->with('laratrust.permission_foreign_key')->once()->andReturn('permission_id');
        Config::shouldReceive('get')->with('laratrust.group_foreign_key')
            ->once()->andReturn('group_id');
        $user->shouldReceive('morphToMany')->andReturn($relationship)->once();
        $relationship->shouldReceive('withPivot')->with('group_id')->andReturn($relationship)->once();
        $relationship->shouldReceive('get')->andReturn($user->permissions)->once();
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
        $user->shouldReceive('permissions')->andReturn($user);
        $user->shouldReceive('sync')->with($permissionsIds)->once()->ordered();
        Cache::shouldReceive('forget')->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->syncPermissions($permissionsIds));
    }

    public function testUserOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();
        $className = snake_case(get_class($user)) . '_id';
        
        $post = new stdClass();
        $post->$className = $user->getKey();

        $post2 = new stdClass();
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
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();
        $post->UserId = $user->getKey();

        $post2 = new stdClass();
        $post2->UserId = 9;

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->owns($post, 'UserId'));
        $this->assertFalse($user->owns($post2, 'UserId'));
    }

    public function testUserCanAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasPermission')->with('edit-post', false)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($post, null)->andReturn(true)->once();
        $user->shouldReceive('hasPermission')->with('update-post', false)->andReturn(false)->once();
        $user->shouldReceive('hasPermission')->with('enhance-post', true)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($post, 'UserID')->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->canAndOwns('edit-post', $post));
        $this->assertFalse($user->canAndOwns('update-post', $post));
        $this->assertFalse($user->canAndOwns('enhance-post', $post, ['requireAll' => true, 'foreignKeyName' => 'UserID']));
    }

    public function testUserHasRoleAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasRole')->with('editor', false)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($post, null)->andReturn(true)->once();
        $user->shouldReceive('hasRole')->with('regular-user', false)->andReturn(false)->once();
        $user->shouldReceive('hasRole')->with('administrator', true)->andReturn(true)->once();
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
    }

    public function testScopeWhereRoleIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $query = m::mock();
        $user = m::mock('HasRoleUser')->makePartial();

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

    public function testBootLaratrustUserTrait()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('bootLaratrustUserTrait');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        HasRoleUser::bootLaratrustUserTrait();
    }
}

class HasRoleUser extends Model implements LaratrustUserInterface
{
    use LaratrustUserTrait;
    use SoftDeletes;

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
}

class OwnableObject implements Ownable
{
    public function ownerKey()
    {
        return 1;
    }
}
