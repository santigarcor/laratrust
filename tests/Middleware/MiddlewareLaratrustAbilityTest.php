<?php

use Illuminate\Support\Facades\Config;
use Laratrust\Middleware\LaratrustAbility;
use Mockery as m;

class MiddlewareLaratrustAbilityTest extends MiddlewareTest
{
    public function testHandle_IsGuestWithNoAbility_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard[guest]');
        $request = $this->mockRequest();

        $middleware = new LaratrustAbility($guard);

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
        $middleware->handle($request, function () {}, 'admin|user', 'edit-users|update-users');
        $this->assertAbortCode(403);
    }

    public function testHandle_IsLoggedInWithNoAbility_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard');
        $request = $this->mockRequest();

        $middleware = new LaratrustAbility($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        $request->user()->shouldReceive('ability')
            ->with(
                ['admin', 'user'],
                ['edit-users', 'update-users'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(['validate_all' => true], ['validate_all' => false])
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
        $middleware->handle($request, function () {}, 'admin|user', 'edit-users|update-users');
        $this->assertAbortCode(403);

        $middleware->handle($request, function () {}, 'admin|user', 'edit-users|update-users', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($request, function () {}, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all');
        $this->assertAbortCode(403);
    }

    public function testHandle_IsLoggedInWithAbility_ShouldNotAbort()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard');
        $request = $this->mockRequest();

        $middleware = new LaratrustAbility($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        $request->user()->shouldReceive('ability')
            ->with(
                ['admin', 'user'],
                ['edit-users', 'update-users'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(['validate_all' => true], ['validate_all' => false])
            )
            ->andReturn(true);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertDidNotAbort();
        $middleware->handle($request, function () {}, 'admin|user', 'edit-users|update-users');
        $this->assertDidNotAbort();

        $middleware->handle($request, function () {}, 'admin|user', 'edit-users|update-users', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($request, function () {}, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all');
        $this->assertDidNotAbort();
    }

    protected function mockRequest()
    {
        $user = m::mock('_mockedUser')->makePartial();

        $request = m::mock('Illuminate\Http\Request')
            ->shouldReceive('user')
            ->andReturn($user)
            ->getMock();

        return $request;
    }
}
