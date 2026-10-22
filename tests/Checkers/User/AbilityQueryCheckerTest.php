<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

class AbilityQueryCheckerTest extends AbilityCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'query');
    }

    public function test_ability_should_return_boolean()
    {
        $this->abilityShouldReturnBooleanAssertions();
    }

    public function test_ability_should_return_array()
    {
        $this->abilityShouldReturnArrayAssertions();
    }

    public function test_ability_should_return_both()
    {
        $this->abilityShouldReturnBothAssertions();
    }

    public function test_ability_should_accept_strings()
    {
        $this->abilityShouldAcceptStringsAssertions();
    }

    public function test_ability_default_options()
    {
        $this->abilityDefaultOptionsAssertions();
    }

    public function test_ability_should_throw_invalid_argument_exception()
    {
        $this->abilityShouldThrowInvalidArgumentExceptionAssertions();
    }
}
