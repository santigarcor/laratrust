<?php

namespace Laratrust\Tests\Checkers\Model;

class LaratrustModelDefaultCheckerTest extends LaratrustModelCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('laratrust.checker', 'default');
    }

    public function testModelCanDisableTheRolesAndPermissionsCaching()
    {
        $this->modelCanDisableTheRolesAndPermissionsCachingAssertions();
    }
}
