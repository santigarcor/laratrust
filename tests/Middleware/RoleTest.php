<?php

declare(strict_types=1);

namespace Laratrust\Tests\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Laratrust\Middleware\Role;
use Laratrust\Tests\Models\User;
use Mockery as m;

class RoleTest extends MiddlewareTest
{
    public function test_handle_is_guest_with_mismatching_role_should_abort403(): void
    {
        $middleware = new Role($this->guard);

        Auth::shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('guest')->andReturn(true);
        App::shouldReceive('abort')
            ->with(403, self::ABORT_MESSAGE)
            ->andReturn(403);

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user'));
    }

    public function test_handle_is_logged_in_with_mismatch_role_should_abort403(): void
    {
        $user = m::mock(User::class)->makePartial();
        $middleware = new Role($this->guard);

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
        App::shouldReceive('abort')
            ->with(403, self::ABORT_MESSAGE)
            ->andReturn(403);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'require_all'));

        $this->assertEquals(403, $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'guard:api|require_all'));
    }

    public function test_handle_is_logged_in_with_matching_role_should_not_abort(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock(User::class)->makePartial();
        $middleware = new Role($this->guard);

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
        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'guard:api'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'require_all'));

        $this->assertNull($middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'require_all|guard:api'));
    }

    public function test_handle_is_logged_in_with_mismatch_role_should_redirect_without_error(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Session::start();
        Config::set('laratrust.middleware.handling', 'redirect');
        $user = m::mock(User::class)->makePartial();
        $middleware = new Role($this->guard);

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
        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'guard:api')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'require_all')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'guard:api|require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'guard:api|require_all')->getContent());

        $this->assertArrayNotHasKey('error', session()->all());
    }

    public function test_handle_is_logged_in_with_mismatch_role_should_redirect_with_error(): void
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Session::start();
        Config::set('laratrust.middleware.handling', 'redirect');
        Config::set('laratrust.middleware.handlers.redirect.message.content', 'The message was flashed');
        $user = m::mock(User::class)->makePartial();
        $middleware = new Role($this->guard);

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
        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'guard:api')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'require_all|guard:api')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'require_all')->getContent());

        $this->assertObjectHasAttributeFallback('content', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'guard:api|require_all'));
        $this->assertStringContainsString('/home', $middleware->handle($this->request, function (): void {}, 'admin|user', 'TeamA', 'guard:api|require_all')->getContent());

        $this->assertArrayHasKey('error', session()->all());
        $this->assertStringContainsString('message', session()->get('error'));
    }

    protected function assertObjectHasAttributeFallback($attributeName, $object)
    {
        return $this->assertTrue(property_exists($object, $attributeName));
    }
}
