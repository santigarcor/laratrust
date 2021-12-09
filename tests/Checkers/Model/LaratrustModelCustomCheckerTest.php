<?php

namespace Laratrust\Tests\Checkers\Model;

use Laratrust\Tests\Checkers\CustomUserChecker;

class LaratrustModelCustomCheckerTest extends LaratrustModelCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('laratrust.user_checker', CustomUserChecker::class);
    }

    public function testModelDisableTheRolesAndPermissionsCaching()
    {
        $this->modelDisableTheRolesAndPermissionsCachingAssertions();
    }
}
