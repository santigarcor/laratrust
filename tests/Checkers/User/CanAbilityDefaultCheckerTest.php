<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

class CanAbilityDefaultCheckerTest extends CanCheckerTestCase
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
