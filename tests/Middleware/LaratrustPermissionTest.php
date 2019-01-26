<?php

namespace Laratrust\Tests\Middleware;

use Mockery as m;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Laratrust\Middleware\LaratrustPermission;

class LaratrustPermissionTest extends MiddlewareTest
{
    public function testHandle_IsGuestWithNoPermission_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $middleware = new LaratrustPermission($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(true);
        App::shouldReceive('abort')->with(403)->andReturn(403);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'users-create|users-update'));
    }

    public function testHandle_IsLoggedInWithNoPermission_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustPermission($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(false);
        App::shouldReceive('abort')->with(403)->andReturn(403);

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
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustPermission($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
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
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Session::start();
        Config::set('laratrust.middleware.handling', 'redirect');
        Config::set('laratrust.middleware.handlers.redirect.message.content', 'The message was flashed');
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustPermission($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api|require_all'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all'));

        $this->assertArrayHasKey('error', session()->all());
        $this->assertContains('message', session()->get('error'));
    }

    public function testHandle_IsLoggedInWithNoPermission_ShouldWithoutError()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Session::start();
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustPermission($this->guard);
        Config::set('laratrust.middleware.handling', 'redirect');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(false);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasPermission')
            ->with(
                ['users-create', 'users-update'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'require_all'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'guard:api|require_all'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'require_all'));

        $this->assertAttributeContains('/home', 'content', $middleware->handle($this->request, function () {
        }, 'users-create|users-update', 'TeamA', 'guard:api|require_all'));

        $this->assertArrayNotHasKey('error', session()->all());
    }
}
