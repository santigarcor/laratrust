<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Support\Facades\Event;
use Laratrust\Events\PermissionAdded;
use Laratrust\Events\PermissionRemoved;
use Laratrust\Events\PermissionSynced;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;

class RoleEventsTest extends LaratrustTestCase
{
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->role = Role::create(['name' => 'role']);
    }

    public function test_an_event_is_dispatched_when_a_permission_is_added()
    {
        $permission = Permission::create(['name' => 'permission']);
        Event::fake();

        $this->role->givePermission($permission);

        Event::assertDispatched(PermissionAdded::class, fn (PermissionAdded $event) => $event->model->is($this->role)
            && $event->permission === $permission->id
            && $event->team === null);
    }

    public function test_an_event_is_dispatched_when_a_permission_is_removed()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->givePermission($permission);
        Event::fake();

        $this->role->removePermission($permission);

        Event::assertDispatched(PermissionRemoved::class, fn (PermissionRemoved $event) => $event->model->is($this->role)
            && $event->permission === $permission->id);
    }

    public function test_an_event_is_dispatched_when_permissions_are_synced()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->role->givePermission($permission);
        Event::fake();

        $this->role->syncPermissions([]);

        Event::assertDispatched(PermissionSynced::class, fn (PermissionSynced $event) => $event->model->is($this->role)
            && $event->changes === ['attached' => [], 'detached' => [$permission->id], 'updated' => []]);
    }
}
