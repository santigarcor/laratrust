<?php

use Illuminate\Support\Facades\Config;
use Laratrust\Middleware\LaratrustRole;
use Mockery as m;

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
        $request = $this->mockRequest();

        $middleware = new LaratrustRole($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(true);
        Config::shouldReceive('get')
            ->with('laratrust.middleware_handling', 'abort')
            ->andReturn('abort');
        Config::shouldReceive('get')
            ->with('laratrust.middleware_params', '403')
            ->andReturn('403');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $middleware->handle($request, function () {}, 'admin|user');
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
        $request = $this->mockRequest();

        $middleware = new LaratrustRole($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        $request->user()->shouldReceive('hasRole')
            ->with(
                ['admin', 'user'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(false);
        Config::shouldReceive('get')->with('laratrust.middleware_handling', 'abort')
            ->andReturn('abort');
        Config::shouldReceive('get')->with('laratrust.middleware_params', '403')
            ->andReturn('403');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $middleware->handle($request, function () {}, 'admin|user');
        $this->assertAbortCode(403);

        $middleware->handle($request, function () {}, 'admin|user', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($request, function () {}, 'admin|user', 'TeamA', 'require_all');
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
        $request = $this->mockRequest();

        $middleware = new LaratrustRole($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        $request->user()->shouldReceive('hasRole')
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
        $this->assertDidNotAbort();
        $middleware->handle($request, function () {}, 'admin|user');
        $this->assertDidNotAbort();

        $middleware->handle($request, function () {}, 'admin|user', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($request, function () {}, 'admin|user', 'TeamA', 'require_all');
        $this->assertDidNotAbort();
    }
}
