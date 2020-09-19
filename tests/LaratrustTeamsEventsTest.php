<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Team;

class LaratrustTeamEventsTest extends LaratrustEventsTestCase
{
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::create(['name' => 'team']);
    }

    public function testListenToThePermissionAttachedEvent()
    {
        $this->listenTo('permission.attached', Team::class);

        $this->assertHasListenersFor('permission.attached', Team::class);
    }

    public function testListenToThePermissionDetachedEvent()
    {
        $this->listenTo('permission.detached', Team::class);

        $this->assertHasListenersFor('permission.detached', Team::class);
    }

    public function testListenToThePermissionSyncedEvent()
    {
        $this->listenTo('permission.synced', Team::class);

        $this->assertHasListenersFor('permission.synced', Team::class);
    }

    public function testAnEventIsFiredWhenPermissionIsAttachedToTeam()
    {
        $permission = Permission::create(['name' => 'permission']);

        Team::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.attached', [$this->team, $permission->id, null], Team::class);

        $this->team->attachPermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionIsDetachedFromTeam()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->team->attachPermission($permission);

        Team::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.detached', [$this->team, $permission->id, null], Team::class);

        $this->team->detachPermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionsAreSynced()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->team->attachPermission($permission);

        Team::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.synced', [
            $this->team,
            [
                'attached' => [], 'detached' => [$permission->id], 'updated' => [],
            ],
            null
        ], Team::class);

        $this->team->syncPermissions([]);
    }
}
