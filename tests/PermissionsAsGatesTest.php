<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Laratrust\LaratrustServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;

class PermissionsAsGatesTest extends LaratrustTestCase
{
    public function testRegistersPermissionsAsGates()
    {
        config()->set('laratrust.permissions_as_gates', true);

        $this->mock(Gate::class)->shouldReceive('before')->once();

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function testDoesNotRegisterPermissionsAsGatesIfDisabledInConfig()
    {
        config()->set('laratrust.permissions_as_gates', false);

        $this->mock(Gate::class)->shouldNotReceive('before');

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function testDoesNotRegisterPermissionsAsGatesByDefault()
    {
        $this->mock(Gate::class)->shouldNotReceive('before');

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }
}
