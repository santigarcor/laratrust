<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\Model;

use Laratrust\Checkers\User\UserDefaultChecker;

class LaratrustModelCustomCheckerTest extends LaratrustModelCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('laratrust.checkers.user', UserDefaultChecker::class);
    }

    public function test_model_disable_the_roles_and_permissions_caching()
    {
        $this->modelDisableTheRolesAndPermissionsCachingAssertions();
    }
}
