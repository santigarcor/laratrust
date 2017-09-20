<?php

use Mockery as m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laratrust\Middleware\LaratrustRole;

class MiddlewareLaratrustRoleTest extends MiddlewareTest
{
    public function testHandle_IsGuestWithMismatchingRole_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard[guest]');
        $middleware = new LaratrustRole($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with('web')->andReturn($guard);
        $guard->shouldReceive('guest')->andReturn(true);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Config::shouldReceive('get')
            ->with('laratrust.middleware.handling', 'abort')
            ->andReturn('abort');
        Config::shouldReceive('get')
            ->with('laratrust.middleware.params', '403')
            ->andReturn('403');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $middleware->handle($this->request, function () {
        }, 'admin|user');
        $this->assertAbortCode(403);
    }

    public function testHandle_IsLoggedInWithMismatchRole_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard');
        $user = m::mock('_mockedUser')->makePartial();
        $middleware = new LaratrustRole($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($guard);
        $guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasRole')
            ->with(
                ['admin', 'user'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(false);
        Config::shouldReceive('get')->with('laratrust.middleware.handling', 'abort')
            ->andReturn('abort');
        Config::shouldReceive('get')->with('laratrust.middleware.params', '403')
            ->andReturn('403');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $middleware->handle($this->request, function () {
        }, 'admin|user');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all|guard:api');
        $this->assertAbortCode(403);
    }

    public function testHandle_IsLoggedInWithMatchingRole_ShouldNotAbort()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard');
        $user = m::mock('_mockedUser')->makePartial();
        $middleware = new LaratrustRole($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($guard);
        $guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasRole')
            ->with(
                ['admin', 'user'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(true);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $middleware->handle($this->request, function () {
        }, 'admin|user');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all|guard:api');
        $this->assertDidNotAbort();
    }
}
