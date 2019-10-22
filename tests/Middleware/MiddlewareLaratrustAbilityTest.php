<?php

namespace Laratrust\Tests\Middleware;

use Mockery as m;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
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
        $middleware = new LaratrustAbility($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(true);
        App::shouldReceive('abort')
            ->with(403, self::ABORT_MESSAGE)
            ->andReturn(403);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users'));
    }

    public function testHandle_IsLoggedInWithNoAbility_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $guard = m::mock('Illuminate\Contracts\Auth\Guard');
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustAbility($guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('ability')
            ->with(
                ['admin', 'user'],
                ['edit-users', 'update-users'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(['validate_all' => true], ['validate_all' => false])
            )
            ->andReturn(false);
        App::shouldReceive('abort')
            ->with(403, self::ABORT_MESSAGE)
            ->andReturn(403);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'require_all|guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'guard:api|require_all'));
    }

    public function testHandle_IsLoggedInWithAbility_ShouldNotAbort()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustAbility($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
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
        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'guard:api'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'guard:api|require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all|guard:api'));
    }
}
