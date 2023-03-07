<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Permission;

class LaratrustRoleEventsTest extends LaratrustEventsTestCase
{
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->role = Role::create(['name' => 'role']);
    }

    public function testListenToThePermissionAttachedEvent()
    {
        $this->listenTo('permission.attached', Role::class);

        $this->assertHasListenersFor('permission.attached', Role::class);
    }

    public function testListenToThePermissionDetachedEvent()
    {
        $this->listenTo('permission.detached', Role::class);

        $this->assertHasListenersFor('permission.detached', Role::class);
    }

    public function testListenToThePermissionSyncedEvent()
    {
        $this->listenTo('permission.synced', Role::class);

        $this->assertHasListenersFor('permission.synced', Role::class);
    }

    public function testAnEventIsFiredWhenPermissionIsAttachedToRole()
    {
        $permission = Permission::create(['name' => 'permission']);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.attached', [$this->role, $permission->id], Role::class);

        $this->role->givePermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionIsDetachedFromRole()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->givePermission($permission);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.detached', [$this->role, $permission->id], Role::class);

        $this->role->removePermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionsAreSynced()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->givePermission($permission);

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
