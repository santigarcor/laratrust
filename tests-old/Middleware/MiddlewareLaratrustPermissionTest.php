<?php

use Mockery as m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laratrust\Middleware\LaratrustPermission;

class MiddlewareLaratrustPermissionTest extends MiddlewareTest
{
    public function testHandle_IsGuestWithNoPermission_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard[guest]');
        $middleware = new LaratrustPermission($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(true);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($guard);
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
        }, 'users-create|users-update');
        $this->assertAbortCode(403);
    }

    public function testHandle_IsLoggedInWithNoPermission_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard');
        $user = m::mock('_mockedUser')->makePartial();
        $middleware = new LaratrustPermission($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($guard);
        $guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
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
        }, 'users-create|users-update');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all|guard:api');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all');
        $this->assertAbortCode(403);
    }

    public function testHandle_IsLoggedInWithPermission_ShouldNotAbort()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard');
        $user = m::mock('_mockedUser')->makePartial();
        $middleware = new LaratrustPermission($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($guard);
        $guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
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
        }, 'users-create|users-update');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api|require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all');
        $this->assertDidNotAbort();
    }
}
