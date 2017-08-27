<?php

use Laratrust\Laratrust;
use Mockery as m;

class LaratrustTest extends PHPUnit_Framework_TestCase
{
    protected $nullFilterTest;
    protected $abortFilterTest;
    protected $customResponseFilterTest;

    protected $expectedResponse;

    public function setUp()
    {
        $this->nullFilterTest = function ($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            $this->assertNull($filterClosure());

            return true;
        };

        $this->abortFilterTest = function ($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            try {
                $filterClosure();
            } catch (Exception $e) {
                $this->assertSame('abort', $e->getMessage());

                return true;
            }

            // If we've made it this far, no exception was thrown and something went wrong
            return false;
        };

        $this->customResponseFilterTest = function ($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            $result = $filterClosure();

            $this->assertSame($this->expectedResponse, $result);

            return true;
        };
    }

    public function tearDown()
    {
        m::close();
    }

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $laratrust = m::mock('Laratrust\Laratrust[user]', [$app]);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $laratrust->shouldReceive('user')->andReturn($user)->twice()->ordered();
        $laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $user->shouldReceive('hasRole')->with('UserRole', null, false)->andReturn(true)->once();
        $user->shouldReceive('hasRole')->with('NonUserRole', null, false)->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($laratrust->hasRole('UserRole'));
        $this->assertFalse($laratrust->hasRole('NonUserRole'));
        $this->assertFalse($laratrust->hasRole('AnyRole'));
    }

    public function testCan()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $laratrust = m::mock('Laratrust\Laratrust[user]', [$app]);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $laratrust->shouldReceive('user')->andReturn($user)->twice()->ordered();
        $laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $user->shouldReceive('hasPermission')->with('user_can', null, false)->andReturn(true)->once();
        $user->shouldReceive('hasPermission')->with('user_cannot', null, false)->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($laratrust->can('user_can'));
        $this->assertFalse($laratrust->can('user_cannot'));
        $this->assertFalse($laratrust->can('any_permission'));
    }

    public function testAbility()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $laratrust = m::mock('Laratrust\Laratrust[user]', [$app]);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $laratrust->shouldReceive('user')->andReturn($user)->twice()->ordered();
        $laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $user->shouldReceive('ability')->with('admin', 'user_can', null, [])->andReturn(true)->once();
        $user->shouldReceive('ability')->with('admin', 'user_cannot', null, [])->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($laratrust->ability('admin', 'user_can'));
        $this->assertFalse($laratrust->ability('admin', 'user_cannot'));
        $this->assertFalse($laratrust->ability('any_role', 'any_permission'));
    }

    public function testUserOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $laratrust = m::mock('Laratrust\Laratrust[user]', [$app]);
        $user = m::mock('_mockedUser');
        $postModel = m::mock('SomeObject');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $laratrust->shouldReceive('user')->andReturn($user)->twice()->ordered();
        $laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $user->shouldReceive('owns')->with($postModel, null)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($postModel, 'UserId')->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($laratrust->owns($postModel, null));
        $this->assertFalse($laratrust->owns($postModel, 'UserId'));
        $this->assertFalse($laratrust->owns($postModel, 'UserId'));
    }

    public function testUserHasRoleAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $laratrust = m::mock('Laratrust\Laratrust[user]', [$app]);
        $user = m::mock('_mockedUser');
        $postModel = m::mock('SomeObject');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $laratrust->shouldReceive('user')->andReturn($user)->once()->ordered();
        $laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $user->shouldReceive('hasRoleAndOwns')->with('admin', $postModel, [])->andReturn(true)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($laratrust->hasRoleAndOwns('admin', $postModel));
        $this->assertFalse($laratrust->hasRoleAndOwns('admin', $postModel));
    }

    public function testUserCanAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $laratrust = m::mock('Laratrust\Laratrust[user]', [$app]);
        $user = m::mock('_mockedUser');
        $postModel = m::mock('SomeObject');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $laratrust->shouldReceive('user')->andReturn($user)->once()->ordered();
        $laratrust->shouldReceive('user')->andReturn(false)->once()->ordered();
        $user->shouldReceive('canAndOwns')->with('update-post', $postModel, [])->andReturn(true)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($laratrust->canAndOwns('update-post', $postModel));
        $this->assertFalse($laratrust->canAndOwns('update-post', $postModel));
    }

    public function testUser()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->auth = m::mock('Auth');
        $laratrust = new Laratrust($app);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->auth->shouldReceive('user')->andReturn($user)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($user, $laratrust->user());
    }
}
