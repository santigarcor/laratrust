<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers;

use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\Enums\Role as EnumsRole;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;

class UserCheckerTest extends LaratrustTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);

        $this->app['config']->set('laratrust.teams.enabled', true);
    }

    public function test_get_roles()
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
            Role::create(['name' => 'role_c'])->id => ['team_id' => $team->id],
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

    public function test_has_role()
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
            Role::create(['name' => 'role_c'])->id => ['team_id' => $team->id],
        ];
        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->user->roles()->attach($roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasRole([]));
        $this->assertTrue($this->user->hasRole(EnumsRole::ROLE_A));
        $this->assertTrue($this->user->hasRole('role_b'));
        $this->assertTrue($this->user->hasRole('role_c'));
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertFalse($this->user->hasRole('role_c'));
        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertTrue($this->user->hasRole('role_c', 'team_a'));
        $this->assertFalse($this->user->hasRole(EnumsRole::ROLE_A, 'team_a'));

        $this->assertTrue($this->user->hasRole('role_a|role_b'));
        $this->assertTrue($this->user->hasRole([EnumsRole::ROLE_A, 'role_b']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c']));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertFalse($this->user->hasRole(['role_a', 'role_c'], 'team_a', true));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], requireAll: true));
        $this->assertFalse($this->user->hasRole(['role_c', 'role_d'], requireAll: true));

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertFalse($this->user->hasRole(['role_c', 'role_d'], requireAll: true));

        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->assertTrue($this->user->hasRole('role_a'));
        $this->assertTrue($this->user->hasRole(['role_a', 'role_c'], 'team_a'));
        $this->assertTrue($this->user->hasRole('role_c', 'team_a'));
    }

    public function test_has_permission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);

        $roleA = Role::create(['name' => 'role_a'])
            ->givePermission(Permission::create(['name' => 'permission_a']));
        $roleB = Role::create(['name' => 'role_b'])
            ->givePermission(Permission::create(['name' => 'permission_b']));

        $this->user->roles()->attach([
            $roleA->id => ['team_id' => null],
            $roleB->id => ['team_id' => $team->id],
        ]);

        $this->user->permissions()->attach([
            Permission::create(['name' => 'permission_c'])->id => ['team_id' => $team->id],
            Permission::create(['name' => 'permission_d'])->id => ['team_id' => null],
        ]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->user->hasPermission([]));
        $this->assertTrue($this->user->hasPermission(EnumsPermission::PERM_A));
        $this->assertTrue($this->user->hasPermission('permission_b', 'team_a'));
        $this->assertTrue($this->user->hasPermission('permission_b', $team));
        $this->assertFalse($this->user->doesntHavePermission(EnumsPermission::PERM_A));
        $this->assertFalse($this->user->doesntHavePermission('permission_b', 'team_a'));
        $this->assertFalse($this->user->doesntHavePermission('permission_b', $team));
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertFalse($this->user->hasPermission('permission_c'));
        $this->assertTrue($this->user->doesntHavePermission('permission_c'));
        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertTrue($this->user->hasPermission('permission_c'));
        $this->assertTrue($this->user->hasPermission('permission_c', 'team_a'));
        $this->assertTrue($this->user->hasPermission('permission_c', $team));
        $this->assertTrue($this->user->hasPermission('permission_d'));
        $this->assertFalse($this->user->hasPermission('permission_e'));
        $this->assertFalse($this->user->doesntHavePermission('permission_c'));
        $this->assertFalse($this->user->doesntHavePermission('permission_c', 'team_a'));
        $this->assertFalse($this->user->doesntHavePermission('permission_c', $team));
        $this->assertFalse($this->user->doesntHavePermission('permission_d'));
        $this->assertTrue($this->user->doesntHavePermission('permission_e'));

        $this->assertTrue($this->user->hasPermission([EnumsPermission::PERM_A, 'permission_b', 'permission_c', 'permission_d', 'permission_e']));
        $this->assertTrue($this->user->hasPermission('permission_a|permission_b|permission_c|permission_d|permission_e'));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_d'], requireAll: true));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], requireAll: true));
        $this->assertFalse($this->user->hasPermission([EnumsPermission::PERM_A, 'permission_b', 'permission_d'], 'team_a', true));
        $this->assertFalse($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], $team, true));
        $this->assertFalse($this->user->hasPermission(['permission_a', 'permission_b', 'permission_e'], requireAll: true));
        $this->assertFalse($this->user->hasPermission(['permission_e', 'permission_f']));

        $this->assertFalse($this->user->doesntHavePermission([EnumsPermission::PERM_A, 'permission_b', 'permission_c', 'permission_d', 'permission_e']));
        $this->assertFalse($this->user->doesntHavePermission('permission_a|permission_b|permission_c|permission_d|permission_e'));
        $this->assertFalse($this->user->doesntHavePermission(['permission_a', 'permission_d'], requireAll: true));
        $this->assertFalse($this->user->doesntHavePermission(['permission_a', 'permission_b', 'permission_d'], requireAll: true));
        $this->assertTrue($this->user->doesntHavePermission([EnumsPermission::PERM_A, 'permission_b', 'permission_d'], 'team_a', true));
        $this->assertTrue($this->user->doesntHavePermission(['permission_a', 'permission_b', 'permission_d'], $team, true));
        $this->assertTrue($this->user->doesntHavePermission(['permission_a', 'permission_b', 'permission_e'], requireAll: true));
        $this->assertTrue($this->user->doesntHavePermission(['permission_e', 'permission_f']));

        $this->app['config']->set('laratrust.teams.enabled', false);
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], 'team_a', true));
        $this->assertTrue($this->user->hasPermission(['permission_a', 'permission_b', 'permission_d'], $team, true));
        $this->assertFalse($this->user->doesntHavePermission(['permission_a', 'permission_b', 'permission_d'], 'team_a', true));
        $this->assertFalse($this->user->doesntHavePermission(['permission_a', 'permission_b', 'permission_d'], $team, true));

        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->assertTrue($this->user->hasPermission('permission_b', 'team_a'));
        $this->assertTrue($this->user->hasPermission('permission_c', 'team_a'));
        $this->assertFalse($this->user->doesntHavePermission('permission_b', 'team_a'));
        $this->assertFalse($this->user->doesntHavePermission('permission_c', 'team_a'));
    }

    public function test_has_permission_with_placeholder_support()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);

        $role = Role::create(['name' => 'role_a'])
            ->givePermissions([
                Permission::create(['name' => 'admin.posts']),
                Permission::create(['name' => 'admin.pages']),
                Permission::create(['name' => 'admin.users']),
            ]);

        $this->user->roles()->attach($role->id);

        $this->user->permissions()->attach([
            Permission::create(['name' => 'config.things'])->id => ['team_id' => $team->id],
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

        $this->assertFalse($this->user->doesntHavePermission('admin.posts'));
        $this->assertFalse($this->user->doesntHavePermission('admin.pages'));
        $this->assertFalse($this->user->doesntHavePermission('admin.users'));
        $this->assertTrue($this->user->doesntHavePermission('admin.config', 'team_a'));

        $this->assertTrue($this->user->hasPermission(['admin.*']));
        $this->assertTrue($this->user->hasPermission(['admin.*']));
        $this->assertTrue($this->user->hasPermission(['config.*'], 'team_a'));
        $this->assertTrue($this->user->hasPermission(['config.*']));
        $this->assertFalse($this->user->hasPermission(['site.*']));

        $this->assertFalse($this->user->doesntHavePermission(['admin.*']));
        $this->assertFalse($this->user->doesntHavePermission(['admin.*']));
        $this->assertFalse($this->user->doesntHavePermission(['config.*'], 'team_a'));
        $this->assertFalse($this->user->doesntHavePermission(['config.*']));
        $this->assertTrue($this->user->doesntHavePermission(['site.*']));
    }

    public function test_user_disable_the_roles_and_permissions_caching()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = Team::create(['name' => 'team_a']);
        $role = Role::create(['name' => 'role_a'])
            ->givePermissions([
                Permission::create(['name' => 'permission_a']),
                Permission::create(['name' => 'permission_b']),
                Permission::create(['name' => 'permission_c']),
            ]);

        $this->user->roles()->attach($role->id);

        $this->user->permissions()->attach([
            Permission::create(['name' => 'permission_d'])->id => ['team_id' => $team->id],
            Permission::create(['name' => 'permission_e'])->id => ['team_id' => $team->id],
        ]);

        // With cache
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');
        $this->assertTrue(Cache::has("laratrust_user_users_{$this->user->id}"));
        $this->user->flushLaratrustCache();

        // Without cache
        $this->app['config']->set('laratrust.cache.enabled', false);
        $this->user->hasRole('some_role');
        $this->user->hasPermission('some_permission');
        $this->assertFalse(Cache::has("laratrust_user_users_{$this->user->id}"));
    }

    public function test_relationship_is_unset_when_a_role_or_permission_is_modified(): void
    {
        Role::query()->create([
            'name' => 'test',
            'display_name' => 'test',
            'description' => 'test',
        ]);
        Permission::query()->create([
            'name' => 'test_permission',
            'display_name' => 'test_permission',
            'description' => 'test_permission',
        ]);

        $this->user->roles;
        $this->user->permissions;
        $this->user->syncRoles(['test']);
        $this->user->syncPermissions(['test_permission']);
        $this->assertTrue($this->user->hasRole('test'));
        $this->assertTrue($this->user->hasPermission('test_permission'));
    }

    public function test_the_team_can_be_skipped_or_require_all_given_by_name(): void
    {
        $this->user->roles()->attach(Role::create(['name' => 'role_a'])->id);

        foreach ([true, false] as $teamsEnabled) {
            $this->app['config']->set('laratrust.teams.enabled', $teamsEnabled);

            // A boolean in the team position is requireAll.
            $this->assertFalse($this->user->hasRole(['role_a', 'role_b'], true));
            $this->assertTrue($this->user->hasRole(['role_a', 'role_b'], false));
            $this->assertFalse($this->user->hasRole(['role_a', 'role_b'], requireAll: true));
            $this->assertFalse($this->user->hasPermission(['permission_a', 'permission_b'], true));
        }
    }
}
