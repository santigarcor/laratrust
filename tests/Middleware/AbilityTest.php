<?php

declare(strict_types=1);

namespace Laratrust\Tests\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Laratrust\Middleware\Ability;
use Laratrust\Tests\Models\User;
use Mockery as m;

class AbilityTest extends MiddlewareTest
{
    public function test_handle_is_guest_with_no_ability_should_abort403(): void
    {
        $middleware = new Ability($this->guard);

        Auth::shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(true);
        App::shouldReceive('abort')
            ->with(403, self::ABORT_MESSAGE)
            ->andReturn(403);

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users'));
    }

    public function test_handle_is_logged_in_with_no_ability_should_abort403(): void
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

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'require_all|guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'TeamA', 'guard:api|require_all'));
    }

    public function test_handle_is_logged_in_with_ability_should_not_abort(): void
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
                m::anyOf(null, 'TeamA'),
                m::anyOf(['validate_all' => true], ['validate_all' => false])
            )
            ->andReturn(true);

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'guard:api'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'guard:api|require_all'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'edit-users|update-users', 'TeamA', 'require_all|guard:api'));
    }
}
