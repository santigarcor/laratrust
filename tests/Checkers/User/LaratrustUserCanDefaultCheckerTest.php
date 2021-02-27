<?php

namespace Laratrust\Tests\Checkers\User;

class LaratrustCanAbilityDefaultCheckerTest extends LaratrustUserCanCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('laratrust.checker', 'default');
        $app['config']->set('laratrust.permissions_as_gates', true);
    }

    public function testCanShouldReturnBoolean()
    {
        $this->canShouldReturnBooleanAssertions();
    }
}
