<?php

declare(strict_types=1);

namespace Laratrust\Tests\Middleware;

use Mockery as m;
use Laratrust\Tests\Models\User;
use Illuminate\Support\Facades\App;
use Laratrust\Middleware\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class PermissionTest extends MiddlewareTest
{
    public function testHandle_IsGuestWithNoPermission_ShouldAbort403()
    {
        $middleware = new Permission($this->guard);

        Auth::shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(true);
        App::shouldReceive('abort')
            ->with(403, self::ABORT_MESSAGE)
            ->andReturn(403);

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'users-create|users-update'));
    }

    public function testHandle_IsLoggedInWithNoPermission_ShouldAbort403()
    {
        $user = m::mock(User::class)->makePartial();
        $middleware = new Permission($this->guard);

        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
                m::anyOf(true, false)
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
        }, 'users-create|users-update'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api|require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all'));
    }

    public function testHandle_IsLoggedInWithPermission_ShouldNotAbort()
    {
        $user = m::mock(User::class)->makePartial();
        $middleware = new Permission($this->guard);

        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
                m::anyOf(true, false)
            )
            ->andReturn(true);

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'users-create|users-update'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api|require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all'));
    }

    public function testHandle_IsLoggedInWithNoPermission_ShouldRedirectWithError()
    {
        Session::start();
        Config::set('laratrust.middleware.handling', 'redirect');
        Config::set('laratrust.middleware.handlers.redirect.message.content', 'The message was flashed');
        $user = m::mock(User::class)->makePartial();
        $middleware = new Permission($this->guard);

        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
                m::anyOf(true, false)
            )
            ->andReturn(false);

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api|require_all')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all')->getContent());

        $this->assertArrayHasKey('error', session()->all());
        $this->assertStringContainsString('message', session()->get('error'));
    }

    public function testHandle_IsLoggedInWithNoPermission_ShouldWithoutError()
    {
        Session::start();
        $user = m::mock(User::class)->makePartial();
        $middleware = new Permission($this->guard);
        Config::set('laratrust.middleware.handling', 'redirect');

        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
                m::anyOf(true, false)
            )
            ->andReturn(false);

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api|require_all')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all')->getContent());

        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all')->getContent());

        $this->assertArrayNotHasKey('error', session()->all());
    }
}
