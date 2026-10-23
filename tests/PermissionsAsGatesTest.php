<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Contracts\Auth\Access\Gate;
use Laratrust\LaratrustServiceProvider;

class PermissionsAsGatesTest extends LaratrustTestCase
{
    public function test_registers_permissions_as_gates(): void
    {
        config()->set('laratrust.permissions_as_gates', true);

        $this->mock(Gate::class)->shouldReceive('before')->once();

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function test_does_not_register_permissions_as_gates_if_disabled_in_config(): void
    {
        config()->set('laratrust.permissions_as_gates', false);

        $this->mock(Gate::class)->shouldNotReceive('before');

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function test_does_not_register_permissions_as_gates_by_default(): void
    {
        $this->mock(Gate::class)->shouldNotReceive('before');

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }
}
