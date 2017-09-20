<?php

use Mockery as m;
use Laratrust\Laratrust;
use Laratrust\Tests\LaratrustTestCase;

class LaratrustTest extends LaratrustTestCase
{
    protected $laratrust;
    protected $user;

    public function setUp()
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

    public function testCan()
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
        $this->assertTrue($this->laratrust->can('user_can'));
        $this->assertFalse($this->laratrust->can('user_cannot'));
        $this->assertFalse($this->laratrust->can('any_permission'));
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

    public function testUserOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $postModel = m::mock('SomeObject');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->twice()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $this->user->shouldReceive('owns')->with($postModel, null)->andReturn(true)->once();
        $this->user->shouldReceive('owns')->with($postModel, 'UserId')->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->laratrust->owns($postModel, null));
        $this->assertFalse($this->laratrust->owns($postModel, 'UserId'));
        $this->assertFalse($this->laratrust->owns($postModel, 'UserId'));
    }

    public function testUserHasRoleAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $postModel = m::mock('SomeObject');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->once()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $this->user->shouldReceive('hasRoleAndOwns')->with('admin', $postModel, [])->andReturn(true)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->laratrust->hasRoleAndOwns('admin', $postModel));
        $this->assertFalse($this->laratrust->hasRoleAndOwns('admin', $postModel));
    }

    public function testUserCanAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $postModel = m::mock('SomeObject');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->laratrust->shouldReceive('user')->andReturn($this->user)->once()->ordered();
        $this->laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $this->user->shouldReceive('canAndOwns')->with('update-post', $postModel, [])->andReturn(true)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($this->laratrust->canAndOwns('update-post', $postModel));
        $this->assertFalse($this->laratrust->canAndOwns('update-post', $postModel));
    }

    public function testUser()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $this->laratrust = new Laratrust($this->app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        \Illuminate\Support\Facades\Auth::shouldReceive('user')->andReturn($this->user)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($this->user, $this->laratrust->user());
    }
}
