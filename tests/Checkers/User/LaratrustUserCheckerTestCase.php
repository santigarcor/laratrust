<?php

namespace Laratrust\Tests\Checkers\User;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustUserCheckerTestCase extends LaratrustTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.teams.enabled', true);
    }

    protected function getRolesAssertions()
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
        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->user->roles()->attach($roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertEquals(['role_a', 'role_b'], $this->user->getRoles());
        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertEquals(['role_a', 'role_b', 'role_c'], $this->user->getRoles());

        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertEquals(['role_c'], $this->user->getRoles('team_a'));
        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertEquals(['role_c'], $this->user->getRoles('team_a'));

        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->assertEquals(['role_a', 'role_b', 'role_c'], $this->user->getRoles());
        $this->assertEquals(['role_c'], $this->user->getRoles('team_a'));
    }

    protected function hasRoleAssertions()
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
        $this->app['config']->set('laratrust.teams.enabled', true);
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
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertFalse($this->user->hasRole('role_c'));
        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertTrue($this->user->hasRole('role_c', 'team_a'));
        $this->assertFalse($this->user->hasRole('role_a', 'team_a'));

        $this->assertTrue($this->user->hasRole('role_a|role_b'));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_b']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertFalse($this->user->hasRole(['role_a', 'role_c'], 'team_a', true));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], true));
        $this->assertFalse($this->user->hasRole(['role_c', 'role_d'], true));

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertFalse($this->user->hasRole(['role_c', 'role_d'], true));

        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->assertTrue($this->user->hasRole('role_a'));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertTrue($this->user->hasRole('role_c', 'team_a'));
    }

    protected function hasPermissionAssertions()
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
            ->attachPermission(Permission::create(['name' => 'permission_b']),$team);

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
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertFalse($this->user->hasPermission('permission_c'));
        $this->app['config']->set('laratrust.teams.strict_check', false);
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

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], 'team_a', true));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], $team, true));

        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->assertTrue($this->user->hasPermission('permission_b', 'team_a'));
        $this->assertTrue($this->user->hasPermission('permission_c', 'team_a'));
    }

    protected function hasPermissionWithPlaceholderSupportAssertions()
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

    public function userDisableTheRolesAndPermissionsCachingAssertions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
        $role = Role::create(['name' => 'role_a'])
            ->attachPermissions([
                Permission::create(['name' => 'permission_a']),
                Permission::create(['name' => 'permission_b']),
                Permission::create(['name' => 'permission_c']),
            ]);

        $this->user->roles()->attach($role->id);

        $this->user->permissions()->attach([
            Permission::create(['name' => 'permission_d'])->id => ['team_id' => $team->id ],
            Permission::create(['name' => 'permission_e'])->id => ['team_id' => $team->id],
        ]);

        // With cache
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');
        $this->assertTrue(Cache::has("laratrust_roles_for_user_{$this->user->id}"));
        $this->assertTrue(Cache::has("laratrust_permissions_for_user_{$this->user->id}"));
        $this->user->flushCache();

        // Without cache
        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');
        $this->assertFalse(Cache::has("laratrust_roles_for_user_{$this->user->id}"));
        $this->assertFalse(Cache::has("laratrust_permissions_for_user_{$this->user->id}"));
    }
}
