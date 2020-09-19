<?php

namespace Laratrust\Tests;

use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\TeamObserver;

/**
 * Class LaratrustTeamEventsTest
 * @property Team $model
 * @property TeamObserver $observer
 * @method  Team getModelClass()
 */
class LaratrustTeamEventsTest extends AbstractLaratrustPermissionEventsTest
{


    protected function setUp(): void
    {
        $this->modelClass = Team::class;
        $this->observer = TeamObserver::class;
        parent::setUp();
        $this->model = Team::create(['name' => 'role']);
    }

}
