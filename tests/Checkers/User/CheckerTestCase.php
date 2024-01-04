<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\Enums\Role as EnumsRole;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Group;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;

abstract class CheckerTestCase extends LaratrustTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
    }

    protected function getRolesAssertions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $group = Group::create(['name' => 'group_a']);
        $roles = [
            Role::create(['name' => 'role_a'])->id,
            Role::create(['name' => 'role_b'])->id,
        ];
        $group->roles()->attach([Role::create(['name' => 'role_c'])->id]);
        $this->user->roles()->attach($roles);
        $this->user->addToGroup($group);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(['role_a', 'role_b', 'role_c'], $this->user->getRoles());
    }

    protected function hasRoleAssertions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $group = Group::create(['name' => 'group_a']);
        $roles = [
            Role::create(['name' => 'role_a'])->id,
            Role::create(['name' => 'role_b'])->id,
        ];
        $group->roles()->attach([Role::create(['name' => 'role_c'])->id]);
        $this->user->roles()->attach($roles);
        $this->user->addToGroup($group);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasRole([]));
        $this->assertTrue($this->user->hasRole(EnumsRole::ROLE_A));
        $this->assertTrue($this->user->hasRole('role_b'));
        $this->assertTrue($this->user->hasRole('role_c'));

        $this->assertTrue($this->user->hasRole('role_a|role_b'));
        $this->assertTrue($this->user->hasRole([EnumsRole::ROLE_A, 'role_b']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], requireAll: true));
        $this->assertFalse($this->user->hasRole(['role_c', 'role_d'], requireAll: true));

        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->assertTrue($this->user->hasRole('role_a'));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c']));
        $this->assertTrue($this->user->hasRole('role_c'));
    }

    protected function hasPermissionAssertions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $group = Group::create(['name' => 'group_a']);

        $roleA = Role::create(['name' => 'role_a'])
            ->givePermission(Permission::create(['name' => 'permission_a']));
        $roleB = Role::create(['name' => 'role_b'])
            ->givePermission(Permission::create(['name' => 'permission_b']));

        $this->user->roles()->attach([$roleA->id]);
        $group->roles()->attach([$roleB->id]);
        $this->user->addToGroup($group);

        $this->user->permissions()->attach([
            Permission::create(['name' => 'permission_c'])->id,
            Permission::create(['name' => 'permission_d'])->id,
        ]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasPermission([]));
        $this->assertTrue($this->user->hasPermission(EnumsPermission::PERM_A));
        $this->assertTrue($this->user->hasPermission('permission_b'));
        $this->assertTrue($this->user->hasPermission('permission_c'));
        $this->assertTrue($this->user->hasPermission('permission_d'));
        $this->assertFalse($this->user->hasPermission('permission_e'));

        $this->assertTrue($this->user->hasPermission([EnumsPermission::PERM_A, 'permission_b', 'permission_c', 'permission_d', 'permission_e']));
        $this->assertTrue($this->user->hasPermission('permission_a|permission_b|permission_c|permission_d|permission_e'));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_d'], requireAll: true));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], requireAll: true));
        $this->assertTrue($this->user->hasPermission([EnumsPermission::PERM_A, 'permission_b', 'permission_d'], true));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], true));
        $this->assertFalse($this->user->hasPermission(['permission_a', 'permission_b', 'permission_e'], requireAll: true));
        $this->assertFalse($this->user->hasPermission(['permission_e', 'permission_f']));
    }

    protected function hasPermissionWithPlaceholderSupportAssertions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $group = Group::create(['name' => 'group_a'])
            ->givePermissions([
                Permission::create(['name' => 'config.things'])->id,
                Permission::create(['name' => 'config.another_things'])->id,
            ]);

        $role = Role::create(['name' => 'role_a'])
            ->givePermissions([
                Permission::create(['name' => 'admin.posts']),
                Permission::create(['name' => 'admin.pages']),
                Permission::create(['name' => 'admin.users']),
            ]);

        $this->user->roles()->attach($role->id);
        $this->user->addToGroup($group);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasPermission('admin.posts'));
        $this->assertTrue($this->user->hasPermission('admin.pages'));
        $this->assertTrue($this->user->hasPermission('admin.users'));
        $this->assertFalse($this->user->hasPermission('admin.config'));

        $this->assertTrue($this->user->hasPermission(['admin.*']));
        $this->assertTrue($this->user->hasPermission(['admin.*']));
        $this->assertTrue($this->user->hasPermission(['config.*']));
        $this->assertFalse($this->user->hasPermission(['site.*']));
    }

    public function userDisableTheRolesAndPermissionsCachingAssertions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $group = Group::create(['name' => 'group_a']);
        $group->permissions()->attach([
            Permission::create(['name' => 'permission_d'])->id,
            Permission::create(['name' => 'permission_e'])->id,
        ]);
        $role = Role::create(['name' => 'role_a'])
            ->givePermissions([
                Permission::create(['name' => 'permission_a']),
                Permission::create(['name' => 'permission_b']),
                Permission::create(['name' => 'permission_c']),
            ]);

        $this->user->roles()->attach($role->id);
        $this->user->addToGroup($group);

        // With cache
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');
        $this->assertTrue(Cache::has("laratrust_cache_for_{$this->user->id}"));
        $this->user->flushCache(false);

        // Without cache
        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');
        $this->assertFalse(Cache::has("laratrust_cache_for_{$this->user->id}"));
    }
}
