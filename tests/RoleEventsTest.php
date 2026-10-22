<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;

class RoleEventsTest extends EventsTestCase
{
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->role = Role::create(['name' => 'role']);
    }

    public function test_listen_to_the_permission_added_event()
    {
        $this->listenTo('permission.added', Role::class);

        $this->assertHasListenersFor('permission.added', Role::class);
    }

    public function test_listen_to_the_permission_removed_event()
    {
        $this->listenTo('permission.removed', Role::class);

        $this->assertHasListenersFor('permission.removed', Role::class);
    }

    public function test_listen_to_the_permission_synced_event()
    {
        $this->listenTo('permission.synced', Role::class);

        $this->assertHasListenersFor('permission.synced', Role::class);
    }

    public function test_an_event_is_fired_when_permission_is_added_to_role()
    {
        $permission = Permission::create(['name' => 'permission']);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.added', [$this->role, $permission->id], Role::class);

        $this->role->givePermission($permission);
    }

    public function test_an_event_is_fired_when_permission_is_removed_from_role()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->givePermission($permission);

        Role::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.removed', [$this->role, $permission->id], Role::class);

        $this->role->removePermission($permission);
    }

    public function test_an_event_is_fired_when_permissions_are_synced()
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
            ],
        ], Role::class);

        $this->role->syncPermissions([]);
    }
}
