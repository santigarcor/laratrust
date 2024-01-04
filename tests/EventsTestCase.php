<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Mockery as m;

class EventsTestCase extends LaratrustTestCase
{
    protected $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->dispatcher = m::mock(\Illuminate\Events\Dispatcher::class)->makePartial();
    }

    /**
     * Listen to a Laratrust event.
     */
    protected function listenTo(string $event, string $modelClass): void
    {
        $method = \Illuminate\Support\Str::camel(str_replace('.', ' ', $event));

        $modelClass::{$method}(function ($user, $roleId) {
            return 'test';
        });
    }

    /**
     * Assert that the dispatcher has listeners for the given event.
     */
    protected function assertHasListenersFor(string $event, string $modelClass): void
    {
        $eventName = "laratrust.{$event}: {$modelClass}";
        $dispatcher = $modelClass::getEventDispatcher();

        $this->assertTrue($dispatcher->hasListeners($eventName));
        $this->assertCount(1, $dispatcher->getListeners($eventName));
        $this->assertEquals('test', $dispatcher->dispatch($eventName, ['user', 'an_id', null])[0]);
    }

    /**
     * Assert the dispatcher fires the fire event with the given data.
     */
    protected function dispatcherShouldFire(string $event, array $payload, string $modelClass): void
    {
        $this->dispatcher->shouldReceive('dispatch')
            ->with(
                "laratrust.{$event}: {$modelClass}",
                $payload
            )
            ->andReturn(null)
            ->once()->ordered();
    }
}
