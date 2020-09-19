<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\UserObserver;

/**
 * Class LaratrustUserEventsTest
 * @property User $model
 * @property UserObserver $observer
 * @method  User getModelClass()
 */
class AbstractLaratrustUserEventsTest extends AbstractLaratrustPermissionEventsTest
{


    protected function setUp(): void
    {
        $this->modelClass = User::class;
        $this->observer = UserObserver::class;
        parent::setUp();
        $this->model = User::create(['name' => 'test', 'email' => 'test@test.com']);

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


    public function testListenToTheRoleSyncedEvent()
    {
        $this->listenTo('role.synced', User::class);

        $this->assertHasListenersFor('role.synced', User::class);
    }


    public function testAnEventIsFiredWhenRoleIsAttachedToUser()
    {
        User::setEventDispatcher($this->dispatcher);
        $role = Role::create(['name' => 'role']);

        $this->dispatcherShouldFire('role.attached', [$this->model, $role->id, null], User::class);

        $this->model->attachRole($role);
    }

    public function testAnEventIsFiredWhenRoleIsDetachedFromUser()
    {
        $role = Role::create(['name' => 'role']);
        $this->model->attachRole($role);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.detached', [$this->model, $role->id, null], User::class);

        $this->model->detachRole($role);
    }

    public function testAnEventIsFiredWhenRolesAreSynced()
    {
        $role = Role::create(['name' => 'role']);

        User::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('role.synced', [
            $this->model,
            [
                'attached' => [$role->id], 'detached' => [], 'updated' => [],
            ],
            null
        ], User::class);

        $this->model->syncRoles([$role]);
    }

    public function testAddObservableClasses()
    {
        $events = [
            'role.attached',
            'role.detached',
            'role.synced',
        ];

        User::laratrustObserve(\Laratrust\Tests\Models\UserObserver::class);

        foreach ($events as $event) {
            $this->assertTrue(User::getEventDispatcher()->hasListeners("laratrust.{$event}: ".User::class));
        }
    }

    public function testObserversShouldBeRemovedAfterFlushEvents()
    {
        $events = [
            'role.attached',
            'role.detached',
            'role.synced',
        ];

        User::laratrustObserve(\Laratrust\Tests\Models\UserObserver::class);
        User::laratrustFlushObservables();

        foreach ($events as $event) {
            $this->assertFalse(User::getEventDispatcher()->hasListeners("laratrust.{$event}: ".User::class));
        }
    }
}
