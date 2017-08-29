<?php

use Mockery as m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laratrust\Middleware\LaratrustAbility;

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
        $middleware = new LaratrustAbility($guard);

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
        }, 'admin|user', 'edit-users|update-users');
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
        $user = m::mock('_mockedUser')->makePartial();
        $middleware = new LaratrustAbility($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($guard);
        $guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('ability')
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
        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'guard:api');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'require_all|guard:api');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all');
        $this->assertAbortCode(403);

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'guard:api|require_all');
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
        $user = m::mock('_mockedUser')->makePartial();
        $middleware = new LaratrustAbility($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $guard->shouldReceive('guest')->andReturn(false);
        Config::shouldReceive('get')->with('auth.defaults.guard')->andReturn('web');
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($guard);
        $guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('ability')
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
        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'guard:api');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'guard:api|require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all');
        $this->assertDidNotAbort();

        $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all|guard:api');
        $this->assertDidNotAbort();
    }
}
