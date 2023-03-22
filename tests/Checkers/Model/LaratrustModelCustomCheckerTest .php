<?php

namespace Laratrust\Tests\Checkers\Model;

use Laratrust\Checkers\User\UserDefaultChecker;

class LaratrustModelCustomCheckerTest  extends LaratrustModelCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('laratrust.checkers.user', UserDefaultChecker::class);
    }

    public function testModelDisableTheRolesAndPermissionsCaching()
    {
        $this->modelDisableTheRolesAndPermissionsCachingAssertions();
    }
}
