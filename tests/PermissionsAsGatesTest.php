<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Gate as GateFacade;
use Laratrust\LaratrustServiceProvider;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Mockery;
use Mockery\LegacyMockInterface;

class PermissionsAsGatesTest extends LaratrustTestCase
{
    public function test_registers_permissions_as_gates(): void
    {
        config()->set('laratrust.permissions_as_gates', true);

        $this->mockGate()->shouldReceive('before')->once();

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function test_does_not_register_permissions_as_gates_if_disabled_in_config(): void
    {
        config()->set('laratrust.permissions_as_gates', false);

        $this->mockGate()->shouldNotReceive('before');

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    public function test_does_not_register_permissions_as_gates_by_default(): void
    {
        $this->mockGate()->shouldNotReceive('before');

        $provider = new LaratrustServiceProvider($this->app);
        $provider->boot();
    }

    /**
     * The panel's gate resolved the Gate facade while booting, so the mock is swapped into the facade.
     */
    protected function mockGate(): LegacyMockInterface
    {
        $gate = Mockery::mock(Gate::class)->shouldIgnoreMissing();
        GateFacade::swap($gate);

        return $gate;
    }

    public function test_only_teams_enums_and_keys_are_used_as_the_team(): void
    {
        config()->set('laratrust.permissions_as_gates', true);
        config()->set('laratrust.teams.enabled', true);
        (new LaratrustServiceProvider($this->app))->boot();
        $this->migrate();

        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $team = Team::create(['name' => 'team_a']);
        $user->givePermission(Permission::create(['name' => 'edit']), $team);
        GateFacade::define('edit', fn (): bool => false);

        $this->assertTrue($user->can('edit', $team));
        $this->assertTrue($user->can('edit', [$team->id, true]));
        $this->assertTrue($user->can('edit', 'team_a'));

        // A model that isn't a team is what the check is about: its key is never used as the team.
        $this->assertSame($team->id, $user->id);
        config()->set('laratrust.teams.strict_check', true);
        $this->assertFalse($user->can('edit', $user));
    }
}
