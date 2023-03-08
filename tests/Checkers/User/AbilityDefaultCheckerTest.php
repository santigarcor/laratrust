<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

class AbilityDefaultCheckerTest extends AbilityCheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'default');
    }

    public function testAbilityShouldReturnBoolean()
    {
        $this->abilityShouldReturnBooleanAssertions();
    }

    public function testAbilityShouldReturnArray()
    {
        $this->abilityShouldReturnArrayAssertions();
    }

    public function testAbilityShouldReturnBoth()
    {
        $this->abilityShouldReturnBothAssertions();
    }

    public function testAbilityShouldAcceptStrings()
    {
        $this->abilityShouldAcceptStringsAssertions();
    }

    public function testAbilityDefaultOptions()
    {
        $this->abilityDefaultOptionsAssertions();
    }

    public function testAbilityShouldThrowInvalidArgumentException()
    {
        $this->abilityShouldThrowInvalidArgumentExceptionAssertions();
    }
}
