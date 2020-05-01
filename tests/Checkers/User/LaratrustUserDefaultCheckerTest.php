<?php

namespace Laratrust\Tests\Checkers\User;

class LaratrustUserDefaultCheckerTest extends LaratrustUserCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'default');
    }

    public function testGetRoles()
    {
        $this->getRolesAssertions();
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

    public function testUserDisableTheRolesAndPermissionsCaching()
    {
        $this->userDisableTheRolesAndPermissionsCachingAssertions();
    }
}
