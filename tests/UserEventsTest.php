<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Support\Facades\Event;
use Laratrust\Events\PermissionAdded;
use Laratrust\Events\PermissionRemoved;
use Laratrust\Events\PermissionSynced;
use Laratrust\Events\RoleAdded;
use Laratrust\Events\RoleRemoved;
use Laratrust\Events\RoleSynced;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;

class UserEventsTest extends LaratrustTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
    }

    public function test_an_event_is_dispatched_when_a_role_is_added(): void
    {
        $role = Role::create(['name' => 'role']);
        Event::fake();

        $this->user->addRole($role);

        Event::assertDispatched(RoleAdded::class, fn (RoleAdded $event): bool => $event->model->is($this->user)
            && $event->role === $role->id
            && $event->team === null);
    }

    public function test_an_event_is_dispatched_when_a_role_is_removed(): void
    {
        $role = Role::create(['name' => 'role']);
        $this->user->addRole($role);
        Event::fake();

        $this->user->removeRole($role);

        Event::assertDispatched(RoleRemoved::class, fn (RoleRemoved $event): bool => $event->model->is($this->user)
            && $event->role === $role->id
            && $event->team === null);
    }

    public function test_an_event_is_dispatched_when_a_permission_is_added(): void
    {
        $permission = Permission::create(['name' => 'permission']);
        Event::fake();

        $this->user->givePermission($permission);

        Event::assertDispatched(PermissionAdded::class, fn (PermissionAdded $event): bool => $event->model->is($this->user)
            && $event->permission === $permission->id
            && $event->team === null);
    }

    public function test_an_event_is_dispatched_when_a_permission_is_removed(): void
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->givePermission($permission);
        Event::fake();

        $this->user->removePermission($permission);

        Event::assertDispatched(PermissionRemoved::class, fn (PermissionRemoved $event): bool => $event->model->is($this->user)
            && $event->permission === $permission->id
            && $event->team === null);
    }

    public function test_an_event_is_dispatched_when_roles_are_synced(): void
    {
        $role = Role::create(['name' => 'role']);
        Event::fake();

        $this->user->syncRoles([$role]);

        Event::assertDispatched(RoleSynced::class, fn (RoleSynced $event): bool => $event->model->is($this->user)
            && $event->changes === ['attached' => [$role->id], 'detached' => [], 'updated' => []]
            && $event->team === null);
    }

    public function test_an_event_is_dispatched_when_permissions_are_synced(): void
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->givePermission($permission);
        Event::fake();

        $this->user->syncPermissions([]);

        Event::assertDispatched(PermissionSynced::class, fn (PermissionSynced $event): bool => $event->model->is($this->user)
            && $event->changes === ['attached' => [], 'detached' => [$permission->id], 'updated' => []]
            && $event->team === null);
    }

    public function test_the_events_always_carry_the_team_key(): void
    {
        $team = Team::create(['name' => 'team_a']);
        $role = Role::create(['name' => 'role']);
        Event::fake();

        // The team is given by name and by model; the events always get its key.
        $this->user->addRole($role, 'team_a');
        $this->user->removeRole($role, $team);
        $this->user->syncRoles([$role], 'team_a');

        Event::assertDispatched(RoleAdded::class, fn (RoleAdded $event): bool => $event->team === $team->id);
        Event::assertDispatched(RoleRemoved::class, fn (RoleRemoved $event): bool => $event->team === $team->id);
        Event::assertDispatched(RoleSynced::class, fn (RoleSynced $event): bool => $event->team === $team->id);
    }

    public function test_the_team_is_null_when_teams_are_disabled(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', false);
        $role = Role::create(['name' => 'role']);
        Event::fake();

        $this->user->addRole($role, 'a_team_that_is_ignored');
        $this->user->removeRole($role, 'a_team_that_is_ignored');

        Event::assertDispatched(RoleAdded::class, fn (RoleAdded $event): bool => $event->team === null);
        Event::assertDispatched(RoleRemoved::class, fn (RoleRemoved $event): bool => $event->team === null);
    }

    public function test_no_event_is_dispatched_when_the_role_was_already_added(): void
    {
        $role = Role::create(['name' => 'role']);
        $this->user->addRole($role);
        Event::fake();

        $this->user->addRole($role);

        Event::assertNotDispatched(RoleAdded::class);
    }
}
