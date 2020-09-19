<?php

namespace Laratrust\Test\Checkers\Role;

use Laratrust\Tests\Checkers\Role\LaratrustRoleCheckerTestCase;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Permission;

class LaratrustRoleDefaultCheckerTest extends LaratrustRoleCheckerTestCase
{
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('laratrust.checker', 'default');

    }

    public function testHasPermission()
    {
         parent::hasPermission();
    }

    public function testHasPermissionInTeam()
    {
         parent::hasPermissionInTeam();
    }
}
