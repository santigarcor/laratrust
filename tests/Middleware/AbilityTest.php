<?php

declare(strict_types=1);

namespace Laratrust\Tests\Middleware;

use Mockery as m;
use Laratrust\Tests\Models\User;
use Laratrust\Middleware\Ability;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AbilityTest extends MiddlewareTest
{
    public function testHandle_IsGuestWithNoAbility_ShouldAbort403()
    {
        $middleware = new Ability($this->guard);

        Auth::shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(true);
        App::shouldReceive('abort')
            ->with(403, self::ABORT_MESSAGE)
            ->andReturn(403);

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'edit-users|update-users'));
    }

    public function testHandle_IsLoggedInWithNoAbility_ShouldAbort403()
    {
        $guard = m::mock(Guard::class);
        $user = m::mock(User::class)->makePartial();
        $middleware = new Ability($guard);

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
        $user = m::mock(User::class)->makePartial();
        $middleware = new Ability($this->guard);

        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('ability')
            ->with(
                ['admin', 'user'],
                ['edit-users', 'update-users'],
                m::anyOf(['validate_all' => true], ['validate_all' => false])
            )
            ->andReturn(true);
        // dd("Here");

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
