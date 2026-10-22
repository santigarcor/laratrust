<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

class QueryCheckerTest extends CheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'query');
    }

    public function test_get_roles()
    {
        $this->getRolesAssertions();
    }

    public function test_has_role()
    {
        $this->hasRoleAssertions();
    }

    public function test_has_permission()
    {
        $this->hasPermissionAssertions();
    }

    public function test_has_permission_with_placeholder_support()
    {
        $this->hasPermissionWithPlaceholderSupportAssertions();
    }

    // public function testUserCanDisableTheRolesAndPermissionsCaching()
    // {
    //     $this->userCanDisableTheRolesAndPermissionsCachingAssertions();
    // }
}
