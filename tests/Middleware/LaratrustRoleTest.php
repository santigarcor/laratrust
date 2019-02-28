<?php

namespace Laratrust\Tests\Middleware;

use Mockery as m;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Laratrust\Middleware\LaratrustRole;

class LaratrustRoleTest extends MiddlewareTest
{
    public function testHandle_IsGuestWithMismatchingRole_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $middleware = new LaratrustRole($this->guard);

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
        }, 'admin|user'));
    }

    public function testHandle_IsLoggedInWithMismatchRole_ShouldAbort403()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustRole($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->guard->shouldReceive('guest')->andReturn(false);
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasRole')
            ->with(
                ['admin', 'user'],
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
        }, 'admin|user'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'guard:api|require_all'));
    }

    public function testHandle_IsLoggedInWithMatchingRole_ShouldNotAbort()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustRole($this->guard);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->guard->shouldReceive('guest')->andReturn(false);
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasRole')
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
        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all|guard:api'));
    }

    public function testHandle_IsLoggedInWithMismatchRole_ShouldRedirectWithoutError()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Session::start();
        Config::set('laratrust.middleware.handling', 'redirect');
        $user = m::mock('Laratrust\Tests\Models\User')->makePartial();
        $middleware = new LaratrustRole($this->guard);


        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->guard->shouldReceive('guest')->andReturn(false);
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasRole')
            ->with(
                ['admin', 'user'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(false);
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'guard:api|require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'guard:api|require_all')->getContent());

        $this->assertArrayNotHasKey('error', session()->all());
    }

    public function testHandle_IsLoggedInWithMismatchRole_ShouldRedirectWithError()
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
        $middleware = new LaratrustRole($this->guard);


        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->guard->shouldReceive('guest')->andReturn(false);
        Auth::shouldReceive('guard')->with(m::anyOf('web', 'api'))->andReturn($this->guard);
        $this->guard->shouldReceive('user')->andReturn($user);
        $user->shouldReceive('hasRole')
            ->with(
                ['admin', 'user'],
                m::anyOf(null, 'TeamA'),
                m::anyOf(true, false)
            )
            ->andReturn(false);
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'guard:api')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'require_all')->getContent());

        $this->assertObjectHasAttribute('content', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'guard:api|require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function () {
        }, 'admin|user', 'TeamA', 'guard:api|require_all')->getContent());

        $this->assertArrayHasKey('error', session()->all());
        $this->assertStringContainsString('message', session()->get('error'));
    }
}
