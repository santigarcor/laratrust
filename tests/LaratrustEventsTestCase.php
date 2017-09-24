<?php

namespace Laratrust\Tests;

use Mockery as m;

class LaratrustEventsTestCase extends LaratrustTestCase
{
    protected $dispatcher;

    public function setUp()
    {
        parent::setUp();

        $this->migrate();
        $this->dispatcher = m::mock('\Illuminate\Events\Dispatcher')->makePartial();
        $this->app['config']->set('laratrust.use_teams', true);
    }

    /**
     * Listen to a Laratrust event.
     *
     * @param  string $event
     * @return void
     */
    protected function listenTo($event, $modelClass)
    {
        $method = \Illuminate\Support\Str::camel(str_replace('.', ' ', $event));

        $modelClass::{$method}(function ($user, $roleId) {
            return 'test';
        });
    }

    /**
     * Assert that the dispatcher has listeners for the given event.
     *
     * @param  string $event
     * @return void
     */
    protected function assertHasListenersFor($event, $modelClass)
    {
        $eventName = "laratrust.{$event}: {$modelClass}";
        $dispatcher = $modelClass::getEventDispatcher();

        $this->assertTrue($dispatcher->hasListeners($eventName));
        $this->assertCount(1, $dispatcher->getListeners($eventName));
        $this->assertEquals('test', $dispatcher->fire($eventName, ['user', 'an_id', null])[0]);
    }

    /**
     * Assert the dispatcher fires the fire event with the given data.
     *
     * @param  string $event
     * @param  array  $payload
     * @param  string $model
     * @return void
     */
    protected function dispatcherShouldFire($event, array $payload, $modelClass)
    {
        $this->dispatcher->shouldReceive('fire')
            ->with(
                "laratrust.{$event}: {$modelClass}",
                $payload
            )
            ->andReturn(null)
            ->once()->ordered();
    }
}
