<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Permission;

class RoleEventsTest extends EventsTestCase
{
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->role = Role::create(['name' => 'role']);
    }

    public function testListenToThePermissionAddedEvent()
    {
        $this->listenTo('permission.added', Role::class);

        $this->assertHasListenersFor('permission.added', Role::class);
    }

    public function testListenToThePermissionRemovedEvent()
    {
        $this->listenTo('permission.removed', Role::class);

        $this->assertHasListenersFor('permission.removed', Role::class);
    }

    public function testListenToThePermissionSyncedEvent()
    {
        $this->listenTo('permission.synced', Role::class);

        $this->assertHasListenersFor('permission.synced', Role::class);
    }

    public function testAnEventIsFiredWhenPermissionIsAddedToRole()
    {
        $permission = Permission::create(['name' => 'permission']);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.added', [$this->role, $permission->id], Role::class);

        $this->role->givePermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionIsRemovedFromRole()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->givePermission($permission);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.removed', [$this->role, $permission->id], Role::class);

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
                'attached' => [],
                'detached' => [$permission->id],
                'updated' => [],
            ]
        ], Role::class);

        $this->role->syncPermissions([]);
    }
}
