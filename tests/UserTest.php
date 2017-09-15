<?php

use Mockery as m;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

abstract class UserTest extends PHPUnit_Framework_TestCase
{
    private $facadeMocks = [];

    public function setUp()
    {
        parent::setUp();

        $app = m::mock('app')->shouldReceive('instance')->getMock();

        $this->facadeMocks['config'] = m::mock('config');
        $this->facadeMocks['cache'] = m::mock('cache');

        Config::setFacadeApplication($app);
        Config::swap($this->facadeMocks['config']);

        Cache::setFacadeApplication($app);
        Cache::swap($this->facadeMocks['cache']);
    }

    public function tearDown()
    {
        m::close();
    }

    protected function mockPermission($permName, $team_id = null)
    {
        $permMock = m::mock('Laratrust\Models\Permission');
        $permMock->name = $permName;
        $permMock->display_name = ucwords(str_replace('_', ' ', $permName));
        $permMock->id = 1;

        $pivot = new stdClass();
        $pivot->team_id = $team_id;
        $permMock->pivot = $pivot;

        return $permMock;
    }

    protected function mockRole($roleName, $team_id = null)
    {
        $roleMock = m::mock('Laratrust\Models\LaratrustRole')->makePartial();

        Config::shouldReceive('get')->with('laratrust.user_models')->andReturn([]);
        $roleMock->name = $roleName;
        $roleMock->perms = [];
        $roleMock->permissions = [];
        $roleMock->id = 1;

        $pivot = new stdClass();
        $pivot->team_id = $team_id;
        $roleMock->pivot = $pivot;

        return $roleMock;
    }

    protected function mockTeam($teamName)
    {
        $teamMock = m::mock('Laratrust\Models\LaratrustTeam')->makePartial();

        Config::shouldReceive('get')->with('laratrust.user_models')->andReturn([]);
        $teamMock->name = $teamName;
        $teamMock->id = 1;

        return $teamMock;
    }
}
