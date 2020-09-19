<?php

namespace Laratrust\Tests\Checkers\Role;

use Laratrust\Models\LaratrustRole;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;

abstract class LaratrustRoleCheckerTestCase extends LaratrustTestCase
{
    /**
     * @var LaratrustRole|null
     */
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->role = Role::create(['name' => 'role']);

        $this->app['config']->set('laratrust.teams.enabled', false);
    }

    public function hasPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
         */
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);

        $this->role->permissions()->attach([$permA->id, $permB->id]);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
         */
        $this->assertTrue($this->role->hasPermission('permission_a'));
        $this->assertTrue($this->role->hasPermission('permission_b'));
        $this->assertFalse($this->role->hasPermission('permission_c'));

        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_b']));
        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_c']));
        $this->assertFalse($this->role->hasPermission(['permission_a', 'permission_c'], null, true));
        $this->assertFalse($this->role->hasPermission(['permission_c', 'permission_d']));
    }

    public function hasPermissionInTeam()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
         */
        $team = Team::create(['name' => 'team_a']);
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);

        $this->role->attachPermission($permA->id, $team);
        $this->role->permissions()->attach([$permB->id]);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
         */
        $isTeamEnabled = config('laratrust.teams.enabled');


        $this->assertTrue($this->role->hasPermission('permission_a', $team));

        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertTrue($this->role->hasPermission('permission_a'));
        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertSame(!$isTeamEnabled, $this->role->hasPermission('permission_a'));


        $this->assertTrue($this->role->hasPermission('permission_b'));
        $this->assertSame(!$isTeamEnabled, $this->role->hasPermission('permission_b', $team));
        $this->assertFalse($this->role->hasPermission('permission_c'));
        $this->assertFalse($this->role->hasPermission('permission_c', $team));

        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_b']));
        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_b'], $team));

        $this->app['config']->set('laratrust.teams.strict_check', false);
        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_c']));

        $this->app['config']->set('laratrust.teams.strict_check', true);
        $this->assertSame(!$isTeamEnabled, $this->role->hasPermission(['permission_a', 'permission_c']));

        $this->assertTrue($this->role->hasPermission(['permission_a', 'permission_c'], $team));
        $this->assertFalse($this->role->hasPermission(['permission_a', 'permission_c'], null, true));
        $this->assertFalse($this->role->hasPermission(['permission_a', 'permission_c'], $team, true));
        $this->assertFalse($this->role->hasPermission(['permission_c', 'permission_d']));
    }
}
