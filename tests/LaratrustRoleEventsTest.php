<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Permission;

class LaratrustRoleEventsTest extends LaratrustEventsTestCase
{
    protected $role;

    public function setUp()
    {
        parent::setUp();
        $this->role = Role::create(['name' => 'role']);
    }

    public function testCanListenToThePermissionAttachedEvent()
    {
        $this->listenTo('permission.attached', Role::class);

        $this->assertHasListenersFor('permission.attached', Role::class);
    }

    public function testCanListenToThePermissionDetachedEvent()
    {
        $this->listenTo('permission.detached', Role::class);

        $this->assertHasListenersFor('permission.detached', Role::class);
    }

    public function testCanListenToThePermissionSyncedEvent()
    {
        $this->listenTo('permission.synced', Role::class);

        $this->assertHasListenersFor('permission.synced', Role::class);
    }

    public function testAnEventIsFiredWhenPermissionIsAttachedToRole()
    {
        $permission = Permission::create(['name' => 'permission']);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.attached', [$this->role, $permission->id], Role::class);

        $this->role->attachPermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionIsDetachedFromRole()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->attachPermission($permission);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.detached', [$this->role, $permission->id], Role::class);

        $this->role->detachPermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionsAreSynced()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->attachPermission($permission);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.synced', [
            $this->role,
            [
                'attached' => [], 'detached' => [$permission->id], 'updated' => [],
            ]
        ], Role::class);

        $this->role->syncPermissions([]);
    }
}
