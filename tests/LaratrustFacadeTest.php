<?php

declare(strict_types=1);

use Mockery as m;
use Laratrust\Laratrust;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\LaratrustTestCase;

class LaratrustFacadeTest extends LaratrustTestCase
{
    protected $laratrust;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->laratrust = m::mock(Laratrust::class, [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->user = m::mock(User::class);
    }

    public function testHasRole()
    {
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->twice()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(null)->once()->ordered();
        $this->user->shouldReceive('hasRole')->with('UserRole', null, false)->andReturn(true)->once();
        $this->user->shouldReceive('hasRole')->with('NonUserRole', null, false)->andReturn(false)->once();

        $this->assertTrue($this->laratrust->hasRole('UserRole'));
        $this->assertFalse($this->laratrust->hasRole('NonUserRole'));
        $this->assertFalse($this->laratrust->hasRole('AnyRole'));
    }

    public function testHasPermission()
    {
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->twice()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(null)->once()->ordered();
        $this->user->shouldReceive('hasPermission')->with('user_can', null, false)->andReturn(true)->once();
        $this->user->shouldReceive('hasPermission')->with('user_cannot', null, false)->andReturn(false)->once();

        $this->assertTrue($this->laratrust->hasPermission('user_can'));
        $this->assertFalse($this->laratrust->hasPermission('user_cannot'));
        $this->assertFalse($this->laratrust->hasPermission('any_permission'));
    }

    public function testAbility()
    {
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->twice()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(null)->once()->ordered();
        $this->user->shouldReceive('ability')->with('admin', 'user_can', null, [])->andReturn(true)->once();
        $this->user->shouldReceive('ability')->with('admin', 'user_cannot', null, [])->andReturn(false)->once();

        $this->assertTrue($this->laratrust->ability('admin', 'user_can'));
        $this->assertFalse($this->laratrust->ability('admin', 'user_cannot'));
        $this->assertFalse($this->laratrust->ability('any_role', 'any_permission'));
    }
}
