<?php

namespace Laratrust\Tests\Checkers\User;

class LaratrustCanAbilityDefaultCheckerTest extends LaratrustUserCanCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'default');
    }

    public function testCanShouldReturnBoolean()
    {
        $this->canShouldReturnBooleanAssertions();
    }
}
