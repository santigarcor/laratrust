<?php

use Illuminate\Support\Facades\Config;
use Laratrust\Middleware\LaratrustPermission;
use Mockery as m;

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
        $request = $this->mockRequest();

        $middleware = new LaratrustPermission($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(true);
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
        $middleware->handle($request, function () {}, 'users-create|users-update');
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
        $request = $this->mockRequest();

        $middleware = new LaratrustPermission($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        $request->user()->shouldReceive('hasPermission')
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
        $middleware->handle($request, function () {}, 'users-create|users-update');
        $this->assertAbortCode(403);

        $middleware->handle($request, function () {}, 'users-create|users-update', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($request, function () {}, 'users-create|users-update', 'TeamA', 'require_all');
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
        $request = $this->mockRequest();

        $middleware = new LaratrustPermission($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        $request->user()->shouldReceive('hasPermission')
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
        $this->assertDidNotAbort();
        $middleware->handle($request, function () {}, 'users-create|users-update');
        $this->assertDidNotAbort();

        $middleware->handle($request, function () {}, 'users-create|users-update', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($request, function () {}, 'users-create|users-update', 'TeamA', 'require_all');
        $this->assertDidNotAbort();
    }
}
