<?php

namespace Laratrust\Tests\Checkers\User;

use Illuminate\Support\Facades\Config;

class LaratrustUserQueryCheckerTest extends LaratrustUserCheckerTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'query');
    }

    public function testHasRole()
    {
        $this->hasRoleAssertions();
    }

    public function testHasPermission()
    {
        $this->hasPermissionAssertions();
    }

    // public function testHasPermissionWithPlaceholderSupport()
    // {
    //     $this->hasPermissionWithPlaceholderSupportAssertions();
    // }

    // public function testUserCanDisableTheRolesAndPermissionsCaching()
    // {
    //     $this->userCanDisableTheRolesAndPermissionsCachingAssertions();
    // }
}
