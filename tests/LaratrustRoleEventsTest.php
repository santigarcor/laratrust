<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\RoleObserver;

/**
 * Class LaratrustRoleEventsTest
 * @property Role $model
 * @property RoleObserver $observer
 * @method  Role getModelClass()
 */
class LaratrustRoleEventsTest extends AbstractLaratrustPermissionEventsTest
{


    protected function setUp(): void
    {
        $this->modelClass = Role::class;
        $this->observer = RoleObserver::class;
        parent::setUp();
        $this->model = Role::create(['name' => 'role']);
    }


}
