<?php

use Mockery as m;
use Laratrust\Laratrust;
use Laratrust\Tests\LaratrustTestCase;

class LaratrustTest extends LaratrustTestCase
{
    protected $laratrust;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->laratrust = m::mock('Laratrust\Laratrust[user]', [$this->app]);
        $this->user = m::mock('_mockedUser');
    }

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->twice()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $this->user->shouldReceive('hasRole')->with('UserRole', null, false)->andReturn(true)->once();
        $this->user->shouldReceive('hasRole')->with('NonUserRole', null, false)->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->laratrust->hasRole('UserRole'));
        $this->assertFalse($this->laratrust->hasRole('NonUserRole'));
        $this->assertFalse($this->laratrust->hasRole('AnyRole'));
    }

    public function testIsAbleTo()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->twice()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $this->user->shouldReceive('hasPermission')->with('user_can', null, false)->andReturn(true)->once();
        $this->user->shouldReceive('hasPermission')->with('user_cannot', null, false)->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->laratrust->isAbleTo('user_can'));
        $this->assertFalse($this->laratrust->isAbleTo('user_cannot'));
        $this->assertFalse($this->laratrust->isAbleTo('any_permission'));
    }

    public function testAbility()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->twice()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $this->user->shouldReceive('ability')->with('admin', 'user_can', null, [])->andReturn(true)->once();
        $this->user->shouldReceive('ability')->with('admin', 'user_cannot', null, [])->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->laratrust->ability('admin', 'user_can'));
        $this->assertFalse($this->laratrust->ability('admin', 'user_cannot'));
        $this->assertFalse($this->laratrust->ability('any_role', 'any_permission'));
    }
}
