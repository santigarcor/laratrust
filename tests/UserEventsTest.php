<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\UserObserver;

class UserEventsTest extends EventsTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
    }

    public function test_listen_to_the_role_added_event()
    {
        $this->listenTo('role.added', User::class);

        $this->assertHasListenersFor('role.added', User::class);
    }

    public function test_listen_to_the_role_removed_event()
    {
        $this->listenTo('role.removed', User::class);

        $this->assertHasListenersFor('role.removed', User::class);
    }

    public function test_listen_to_the_permission_added_event()
    {
        $this->listenTo('permission.added', User::class);

        $this->assertHasListenersFor('permission.added', User::class);
    }

    public function test_listen_to_the_permission_removed_event()
    {
        $this->listenTo('permission.removed', User::class);

        $this->assertHasListenersFor('permission.removed', User::class);
    }

    public function test_listen_to_the_role_synced_event()
    {
        $this->listenTo('role.synced', User::class);

        $this->assertHasListenersFor('role.synced', User::class);
    }

    public function test_listen_to_the_permission_synced_event()
    {
        $this->listenTo('permission.synced', User::class);

        $this->assertHasListenersFor('permission.synced', User::class);
    }

    public function test_an_event_is_fired_when_role_is_added_to_user()
    {
        User::setEventDispatcher($this->dispatcher);
        $role = Role::create(['name' => 'role']);

        $this->dispatcherShouldFire('role.added', [$this->user, $role->id, null], User::class);

        $this->user->addRole($role);
    }

    public function test_an_event_is_fired_when_role_is_removed_from_user()
    {
        $role = Role::create(['name' => 'role']);
        $this->user->addRole($role);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.removed', [$this->user, $role->id, null], User::class);

        $this->user->removeRole($role);
    }

    public function test_an_event_is_fired_when_permission_is_added_to_user()
    {
        $permission = Permission::create(['name' => 'permission']);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.added', [$this->user, $permission->id, null], User::class);

        $this->user->givePermission($permission);
    }

    public function test_an_event_is_fired_when_permission_is_removed_from_user()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->givePermission($permission);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.removed', [$this->user, $permission->id, null], User::class);

        $this->user->removePermission($permission);
    }

    public function test_an_event_is_fired_when_roles_are_synced()
    {
        $role = Role::create(['name' => 'role']);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.synced', [
            $this->user,
            [
                'attached' => [$role->id],
                'detached' => [],
                'updated' => [],
            ],
            null,
        ], User::class);

        $this->user->syncRoles([$role]);
    }

    public function test_an_event_is_fired_when_permissions_are_synced()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->givePermission($permission);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.synced', [
            $this->user,
            [
                'attached' => [],
                'detached' => [$permission->id],
                'updated' => [],
            ],
            null,
        ], User::class);

        $this->user->syncPermissions([]);
    }

    public function test_add_observable_classes()
    {
        $events = [
            'role.added',
            'role.removed',
            'permission.added',
            'permission.removed',
            'role.synced',
            'permission.synced',
        ];

        User::laratrustObserve(UserObserver::class);

        foreach ($events as $event) {
            $this->assertTrue(User::getEventDispatcher()->hasListeners("laratrust.{$event}: ".User::class));
        }
    }

    public function test_observers_should_be_removed_after_flush_events()
    {
        $events = [
            'role.added',
            'role.removed',
            'permission.added',
            'permission.removed',
            'role.synced',
            'permission.synced',
        ];

        User::laratrustObserve(UserObserver::class);
        User::laratrustFlushObservables();

        foreach ($events as $event) {
            $this->assertFalse(User::getEventDispatcher()->hasListeners("laratrust.{$event}: ".User::class));
        }
    }
}
