<?php

namespace Laratrust\Tests\Checkers\User;

use Illuminate\Support\Facades\Config;

class LaratrustUserDefaultCheckerTest extends LaratrustUserCheckerTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'default');
    }

    public function testHasRole()
    {
        $this->hasRoleAssertions();
    }

    public function testHasPermission()
    {
        $this->hasPermissionAssertions();
    }

    public function testHasPermissionWithPlaceholderSupport()
    {
        $this->hasPermissionWithPlaceholderSupportAssertions();
    }

    public function testUserCanDisableTheRolesAndPermissionsCaching()
    {
        $this->userCanDisableTheRolesAndPermissionsCachingAssertions();
    }
}
