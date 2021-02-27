<?php

namespace Laratrust\Tests;

use Illuminate\Contracts\Auth\Access\Gate;
use Laratrust\LaratrustServiceProvider;
use Mockery as m;
use Mockery;

class LaratrustPermissionsAsGateTest extends LaratrustTestCase
{
    public function testRegistersPermissionsAsGate()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        config()->set('laratrust.permissions_as_gates', true);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->mock(Gate::class, fn ($mock) => $mock->shouldReceive('before')->once());

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function testDoesNotRegisterPermissionsAsGateIfDisabledInConfig()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        config()->set('laratrust.permissions_as_gates', false);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->mock(Gate::class, fn ($mock) => $mock->shouldNotReceive('before'));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function testDoesNotRegisterPermissionsAsGateByDefault()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->mock(Gate::class, fn ($mock) => $mock->shouldNotReceive('before'));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }
}
