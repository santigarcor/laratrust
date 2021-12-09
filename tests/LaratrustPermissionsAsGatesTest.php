<?php

namespace Laratrust\Tests;

use Illuminate\Contracts\Auth\Access\Gate;
use Laratrust\LaratrustServiceProvider;
use Mockery as m;
use Mockery;

class LaratrustPermissionsAsGatesTest extends LaratrustTestCase
{
    public function testRegistersPermissionsAsGates()
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
        $this->mock(Gate::class)->shouldReceive('before')->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function testDoesNotRegisterPermissionsAsGatesIfDisabledInConfig()
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
        $this->mock(Gate::class)->shouldNotReceive('before');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function testDoesNotRegisterPermissionsAsGatesByDefault()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->mock(Gate::class)->shouldNotReceive('before');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }
}
