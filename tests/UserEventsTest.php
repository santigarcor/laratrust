<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\Permission;

class UserEventsTest extends EventsTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
    }

    public function testListenToTheRoleAddedEvent()
    {
        $this->listenTo('role.added', User::class);

        $this->assertHasListenersFor('role.added', User::class);
    }

    public function testListenToTheRoleRemovedEvent()
    {
        $this->listenTo('role.removed', User::class);

        $this->assertHasListenersFor('role.removed', User::class);
    }

    public function testListenToThePermissionAddedEvent()
    {
        $this->listenTo('permission.added', User::class);

        $this->assertHasListenersFor('permission.added', User::class);
    }

    public function testListenToThePermissionRemovedEvent()
    {
        $this->listenTo('permission.removed', User::class);

        $this->assertHasListenersFor('permission.removed', User::class);
    }

    public function testListenToTheRoleSyncedEvent()
    {
        $this->listenTo('role.synced', User::class);

        $this->assertHasListenersFor('role.synced', User::class);
    }

    public function testListenToThePermissionSyncedEvent()
    {
        $this->listenTo('permission.synced', User::class);

        $this->assertHasListenersFor('permission.synced', User::class);
    }

    public function testAnEventIsFiredWhenRoleIsAddedToUser()
    {
        User::setEventDispatcher($this->dispatcher);
        $role = Role::create(['name' => 'role']);

        $this->dispatcherShouldFire('role.added', [$this->user, $role->id], User::class);

        $this->user->addRole($role);
    }

    public function testAnEventIsFiredWhenRoleIsRemovedFromUser()
    {
        $role = Role::create(['name' => 'role']);
        $this->user->addRole($role);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.removed', [$this->user, $role->id], User::class);

        $this->user->removeRole($role);
    }

    public function testAnEventIsFiredWhenPermissionIsAddedToUser()
    {
        $permission = Permission::create(['name' => 'permission']);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.added', [$this->user, $permission->id], User::class);

        $this->user->givePermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionIsRemovedFromUser()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->givePermission($permission);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.removed', [$this->user, $permission->id], User::class);

        $this->user->removePermission($permission);
    }

    public function testAnEventIsFiredWhenRolesAreSynced()
    {
        $role = Role::create(['name' => 'role']);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.synced', [
            $this->user,
            [
                'attached' => [$role->id],
                'detached' => [],
                'updated' => [],
            ]
        ], User::class);

        $this->user->syncRoles([$role]);
    }

    public function testAnEventIsFiredWhenPermissionsAreSynced()
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
            ]
        ], User::class);

        $this->user->syncPermissions([]);
    }

    public function testAddObservableClasses()
    {
        $events = [
            'role.added',
            'role.removed',
            'permission.added',
            'permission.removed',
            'role.synced',
            'permission.synced',
        ];

        User::laratrustObserve(\Laratrust\Tests\Models\UserObserver::class);

        foreach ($events as $event) {
            $this->assertTrue(User::getEventDispatcher()->hasListeners("laratrust.{$event}: " . User::class));
        }
    }

    public function testObserversShouldBeRemovedAfterFlushEvents()
    {
        $events = [
            'role.added',
            'role.removed',
            'permission.added',
            'permission.removed',
            'role.synced',
            'permission.synced',
        ];

        User::laratrustObserve(\Laratrust\Tests\Models\UserObserver::class);
        User::laratrustFlushObservables();

        foreach ($events as $event) {
            $this->assertFalse(User::getEventDispatcher()->hasListeners("laratrust.{$event}: " . User::class));
        }
    }
}
