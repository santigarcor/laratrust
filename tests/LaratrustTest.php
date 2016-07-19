<?php

use Laratrust\Laratrust;
use Illuminate\Support\Facades\Facade;
use Mockery as m;

class LaratrustTest extends PHPUnit_Framework_TestCase
{
    protected $nullFilterTest;
    protected $abortFilterTest;
    protected $customResponseFilterTest;

    protected $expectedResponse;

    public function setUp()
    {
        $this->nullFilterTest = function($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            $this->assertNull($filterClosure());

            return true;
        };

        $this->abortFilterTest = function($filterClosure) {
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

        $this->customResponseFilterTest = function($filterClosure) {
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
        $laratrust->shouldReceive('user')
            ->andReturn($user)
            ->twice()->ordered();

        $laratrust->shouldReceive('user')
            ->andReturn(false)
            ->once()->ordered();

        $user->shouldReceive('hasRole')
            ->with('UserRole', false)
            ->andReturn(true)
            ->once();

        $user->shouldReceive('hasRole')
            ->with('NonUserRole', false)
            ->andReturn(false)
            ->once();

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
        $laratrust->shouldReceive('user')
            ->andReturn($user)
            ->twice()->ordered();

        $laratrust->shouldReceive('user')
            ->andReturn(false)
            ->once()->ordered();

        $user->shouldReceive('can')
            ->with('user_can', false)
            ->andReturn(true)
            ->once();

        $user->shouldReceive('can')
            ->with('user_cannot', false)
            ->andReturn(false)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($laratrust->can('user_can'));
        $this->assertFalse($laratrust->can('user_cannot'));
        $this->assertFalse($laratrust->can('any_permission'));
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
        $app->auth->shouldReceive('user')
            ->andReturn($user)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($user, $laratrust->user());
    }

    public function testRouteNeedsRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $laratrust = new Laratrust($app);

        $route = 'route';
        $oneRole = 'RoleA';
        $manyRole = ['RoleA', 'RoleB', 'RoleC'];

        $oneRoleFilterName  = $this->makeFilterName($route, [$oneRole]);
        $manyRoleFilterName = $this->makeFilterName($route, $manyRole);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($oneRoleFilterName, $manyRoleFilterName), m::type('Closure'))
            ->twice()->ordered();

        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($oneRoleFilterName, $manyRoleFilterName))
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $laratrust->routeNeedsRole($route, $oneRole);
        $laratrust->routeNeedsRole($route, $manyRole);
    }

    public function testRouteNeedsPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $laratrust = new Laratrust($app);

        $route = 'route';
        $onePerm = 'can_a';
        $manyPerm = ['can_a', 'can_b', 'can_c'];

        $onePermFilterName = $this->makeFilterName($route, [$onePerm]);
        $manyPermFilterName = $this->makeFilterName($route, $manyPerm);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($onePermFilterName, $manyPermFilterName), m::type('Closure'))
            ->twice()->ordered();

        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($onePermFilterName, $manyPermFilterName))
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $laratrust->routeNeedsPermission($route, $onePerm);
        $laratrust->routeNeedsPermission($route, $manyPerm);
    }

    public function testRouteNeedsRoleOrPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $laratrust = new Laratrust($app);

        $route = 'route';
        $oneRole = 'RoleA';
        $manyRole = ['RoleA', 'RoleB', 'RoleC'];
        $onePerm = 'can_a';
        $manyPerm = ['can_a', 'can_b', 'can_c'];

        $oneRoleOnePermFilterName = $this->makeFilterName($route, [$oneRole], [$onePerm]);
        $oneRoleManyPermFilterName = $this->makeFilterName($route, [$oneRole], $manyPerm);
        $manyRoleOnePermFilterName = $this->makeFilterName($route, $manyRole, [$onePerm]);
        $manyRoleManyPermFilterName = $this->makeFilterName($route, $manyRole, $manyPerm);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(
                m::anyOf(
                    $oneRoleOnePermFilterName,
                    $oneRoleManyPermFilterName,
                    $manyRoleOnePermFilterName,
                    $manyRoleManyPermFilterName
                ),
                m::type('Closure')
            )
            ->times(4)->ordered();

        $app->router->shouldReceive('when')
            ->with(
                $route,
                m::anyOf(
                    $oneRoleOnePermFilterName,
                    $oneRoleManyPermFilterName,
                    $manyRoleOnePermFilterName,
                    $manyRoleManyPermFilterName
                )
            )
            ->times(4);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $laratrust->routeNeedsRoleOrPermission($route, $oneRole, $onePerm);
        $laratrust->routeNeedsRoleOrPermission($route, $oneRole, $manyPerm);
        $laratrust->routeNeedsRoleOrPermission($route, $manyRole, $onePerm);
        $laratrust->routeNeedsRoleOrPermission($route, $manyRole, $manyPerm);
    }

    public function simpleFilterDataProvider()
    {
        return [
            // Filter passes, null is returned
            [true, 'nullFilterTest'],
            // Filter fails, App::abort() is called
            [false, 'abortFilterTest', true],
            // Filter fails, custom response is returned
            [false, 'customResponseFilterTest', false, new stdClass()]
        ];
    }

    /**
     * @dataProvider simpleFilterDataProvider
     */
    public function testFilterGeneratedByRouteNeedsRole($returnValue, $filterTest, $abort = false, $expectedResponse = null)
    {
        $this->filterTestExecution('routeNeedsRole', 'hasRole', $returnValue, $filterTest, $abort, $expectedResponse);
    }

    /**
     * @dataProvider simpleFilterDataProvider
     */
    public function testFilterGeneratedByRouteNeedsPermission($returnValue, $filterTest, $abort = false, $expectedResponse = null)
    {
        $this->filterTestExecution('routeNeedsPermission', 'can', $returnValue, $filterTest, $abort, $expectedResponse);
    }

    protected function filterTestExecution($methodTested, $mockedMethod, $returnValue, $filterTest, $abort, $expectedResponse)
    {
        // Mock Objects
        $app         = m::mock('Illuminate\Foundation\Application');
        $app->router = m::mock('Route');
        $laratrust     = m::mock("Laratrust\Laratrust[$mockedMethod]", [$app]);

        // Static values
        $route       = 'route';
        $methodValue = 'role-or-permission';
        $filterName  = $this->makeFilterName($route, [$methodValue]);

        $app->router->shouldReceive('when')->with($route, $filterName)->once();
        $app->router->shouldReceive('filter')->with($filterName, m::on($this->$filterTest))->once();

        if ($abort) {
            $app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();
        }

        $this->expectedResponse = $expectedResponse;

        $laratrust->shouldReceive($mockedMethod)->with($methodValue, m::any(true, false))->andReturn($returnValue)->once();
        $laratrust->$methodTested($route, $methodValue, $expectedResponse);
    }

    public function routeNeedsRoleOrPermissionFilterDataProvider()
    {
        return [
            // Both role and permission pass, null is returned
            [true,  true,  'nullFilterTest'],
            [true,  true,  'nullFilterTest', true],
            // Role OR permission fail, require all is false, null is returned
            [false, true,  'nullFilterTest'],
            [true,  false, 'nullFilterTest'],
            // Role and/or permission fail, App::abort() is called
            [false, true,  'abortFilterTest', true,  true],
            [true,  false, 'abortFilterTest', true,  true],
            [false, false, 'abortFilterTest', false, true],
            [false, false, 'abortFilterTest', true,  true],
            // Role and/or permission fail, custom response is returned
            [false, true,  'customResponseFilterTest', true,  false, new stdClass()],
            [true,  false, 'customResponseFilterTest', true,  false, new stdClass()],
            [false, false, 'customResponseFilterTest', false, false, new stdClass()],
            [false, false, 'customResponseFilterTest', true,  false, new stdClass()]
        ];
    }

    /**
     * @dataProvider routeNeedsRoleOrPermissionFilterDataProvider
     */
    public function testFilterGeneratedByRouteNeedsRoleOrPermission(
        $roleIsValid, $permIsValid, $filterTest, $requireAll = false, $abort = false, $expectedResponse = null
    ) {
        $app         = m::mock('Illuminate\Foundation\Application');
        $app->router = m::mock('Route');
        $laratrust     = m::mock('Laratrust\Laratrust[hasRole, can]', [$app]);

        // Static values
        $route      = 'route';
        $roleName   = 'UserRole';
        $permName   = 'user-permission';
        $filterName = $this->makeFilterName($route, [$roleName], [$permName]);

        $app->router->shouldReceive('when')->with($route, $filterName)->once();
        $app->router->shouldReceive('filter')->with($filterName, m::on($this->$filterTest))->once();

        $laratrust->shouldReceive('hasRole')->with($roleName, $requireAll)->andReturn($roleIsValid)->once();
        $laratrust->shouldReceive('can')->with($permName, $requireAll)->andReturn($permIsValid)->once();

        if ($abort) {
            $app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();
        }

        $this->expectedResponse = $expectedResponse;

        $laratrust->routeNeedsRoleOrPermission($route, $roleName, $permName, $expectedResponse, $requireAll);
    }

    protected function makeFilterName($route, array $roles, array $permissions = null)
    {
        if (is_null($permissions)) {
            return implode('_', $roles) . '_' . substr(md5($route), 0, 6);
        } else {
            return implode('_', $roles) . '_' . implode('_', $permissions) . '_' . substr(md5($route), 0, 6);
        }
    }
}
