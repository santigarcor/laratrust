<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\Permission;

class LaratrustUserEventsTest extends LaratrustEventsTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
    }

    public function testListenToTheRoleAttachedEvent()
    {
        $this->listenTo('role.attached', User::class);

        $this->assertHasListenersFor('role.attached', User::class);
    }

    public function testListenToTheRoleDetachedEvent()
    {
        $this->listenTo('role.detached', User::class);

        $this->assertHasListenersFor('role.detached', User::class);
    }

    public function testListenToThePermissionAttachedEvent()
    {
        $this->listenTo('permission.attached', User::class);

        $this->assertHasListenersFor('permission.attached', User::class);
    }

    public function testListenToThePermissionDetachedEvent()
    {
        $this->listenTo('permission.detached', User::class);

        $this->assertHasListenersFor('permission.detached', User::class);
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

    public function testAnEventIsFiredWhenRoleIsAttachedToUser()
    {
        User::setEventDispatcher($this->dispatcher);
        $role = Role::create(['name' => 'role']);

        $this->dispatcherShouldFire('role.attached', [$this->user, $role->id, null], User::class);

        $this->user->attachRole($role);
    }

    public function testAnEventIsFiredWhenRoleIsDetachedFromUser()
    {
        $role = Role::create(['name' => 'role']);
        $this->user->attachRole($role);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.detached', [$this->user, $role->id, null], User::class);

        $this->user->detachRole($role);
    }

    public function testAnEventIsFiredWhenPermissionIsAttachedToUser()
    {
        $permission = Permission::create(['name' => 'permission']);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.attached', [$this->user, $permission->id, null], User::class);

        $this->user->attachPermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionIsDetachedFromUser()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->attachPermission($permission);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.detached', [$this->user, $permission->id, null], User::class);

        $this->user->detachPermission($permission);
    }

    public function testAnEventIsFiredWhenRolesAreSynced()
    {
        $role = Role::create(['name' => 'role']);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.synced', [
            $this->user,
            [
                'attached' => [$role->id], 'detached' => [], 'updated' => [],
            ],
            null
        ], User::class);

        $this->user->syncRoles([$role]);
    }

    public function testAnEventIsFiredWhenPermissionsAreSynced()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->attachPermission($permission);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.synced', [
            $this->user,
            [
                'attached' => [], 'detached' => [$permission->id], 'updated' => [],
            ],
            null
        ], User::class);

        $this->user->syncPermissions([]);
    }

    public function testAddObservableClasses()
    {
        $events = [
            'role.attached',
            'role.detached',
            'permission.attached',
            'permission.detached',
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
            'role.attached',
            'role.detached',
            'permission.attached',
            'permission.detached',
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
