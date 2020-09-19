<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\User;

/**
 * Class LaratrustPermissionEventsTest
 * @package Laratrust\Tests
 */
abstract class AbstractLaratrustPermissionEventsTest extends LaratrustEventsTestCase
{
    protected $model;
    /**
     * @var string
     */
    protected $modelClass;
    /**
     * @var string
     */
    protected $observer;


    protected function getModelClass()
    {
        return $this->modelClass;
    }


    public function testListenToThePermissionAttachedEvent()
    {
        $this->listenTo('permission.attached', $this->getModelClass());

        $this->assertHasListenersFor('permission.attached', $this->getModelClass());
    }

    public function testListenToThePermissionDetachedEvent()
    {
        $this->listenTo('permission.detached', $this->getModelClass());

        $this->assertHasListenersFor('permission.detached', $this->getModelClass());
    }


    public function testListenToThePermissionSyncedEvent()
    {
        $this->listenTo('permission.synced', $this->getModelClass());

        $this->assertHasListenersFor('permission.synced', $this->getModelClass());
    }


    public function testAnEventIsFiredWhenPermissionIsAttachedToModel()
    {
        $permission = Permission::create(['name' => 'permission']);

        $this->getModelClass()::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.attached', [$this->model, $permission->id, null], $this->getModelClass());

        $this->model->attachPermission($permission);
    }

    public function testAnEventIsFiredWhenPermissionIsDetachedFromModel()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->model->attachPermission($permission);

        $this->getModelClass()::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.detached', [$this->model, $permission->id, null], $this->getModelClass());

        $this->model->detachPermission($permission);
    }


    public function testAnEventIsFiredWhenPermissionsAreSynced()
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->model->attachPermission($permission);

        $this->getModelClass()::setEventDispatcher($this->dispatcher);

        $this->dispatcherShouldFire('permission.synced', [
            $this->model,
            [
                'attached' => [], 'detached' => [$permission->id], 'updated' => [],
            ],
            null
        ], $this->getModelClass());

        $this->model->syncPermissions([]);
    }

    public function testAddObservablePermissionsClasses()
    {
        $events = [
            'permission.attached',
            'permission.detached',
            'permission.synced',
        ];

        $this->getModelClass()::laratrustObserve($this->observer);

        foreach ($events as $event) {
            $this->assertTrue(User::getEventDispatcher()->hasListeners("laratrust.{$event}: ".$this->getModelClass()));
        }
    }

    public function testObserversShouldBeRemovedAfterFlushEventsPermissions()
    {
        $events = [
            'permission.attached',
            'permission.detached',
            'permission.synced',
        ];

        $this->getModelClass()::laratrustObserve($this->observer);
        $this->getModelClass()::laratrustFlushObservables();

        foreach ($events as $event) {
            $this->assertFalse($this->getModelClass()::getEventDispatcher()->hasListeners("laratrust.{$event}: ".$this->getModelClass()));
        }
    }

}
