<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Tests\Migrations\LaratrustSetupTables;
use Laratrust\Tests\Migrations\OthersMigration;
use Laratrust\Tests\Migrations\UsersMigration;
use Laratrust\Tests\Models\Other;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The role, permission and team models get one relationship per key in "laratrust.user_models".
 */
class UserRelationshipsTest extends LaratrustTestCase
{
    protected User $user;

    protected Other $other;

    protected Team $team;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // The relationships are registered at boot, so both user models are configured before it.
        $app['config']->set('laratrust.teams.enabled', true);
        $app['config']->set('laratrust.user_models', [
            'users' => User::class,
            'others' => Other::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([UsersMigration::class, OthersMigration::class, LaratrustSetupTables::class] as $migration) {
            (new $migration)->up();
        }

        $this->user = User::create(['name' => 'user', 'email' => 'user@test.com']);
        $this->other = Other::create(['name' => 'other', 'email' => 'other@test.com']);
        $this->team = Team::create(['name' => 'team_a']);
    }

    /**
     * @return array<string, array{class-string<Model>, string}>
     */
    public static function modelsAndRelationships(): array
    {
        return [
            'role users' => [Role::class, 'users'],
            'role others' => [Role::class, 'others'],
            'permission users' => [Permission::class, 'users'],
            'permission others' => [Permission::class, 'others'],
            'team users' => [Team::class, 'users'],
            'team others' => [Team::class, 'others'],
        ];
    }

    #[DataProvider('modelsAndRelationships')]
    public function test_the_relationship_is_available_as_method_and_attribute(string $class, string $relationship): void
    {
        $model = new $class;

        $this->assertInstanceOf(MorphToMany::class, $model->{$relationship}());
        $this->assertTrue($model->{$relationship}->isEmpty());
    }

    public function test_role_users_can_be_eager_loaded_and_queried(): void
    {
        $role = Role::create(['name' => 'role']);
        $this->user->addRole($role, $this->team);
        $this->other->addRole($role, $this->team);

        $this->assertRelated(Role::with(['users', 'others'])->find($role->id));
        $this->assertTrue(Role::whereHas('others', fn (Builder $query) => $query->whereKey($this->other->id))->exists());
    }

    public function test_permission_users_can_be_eager_loaded_and_queried(): void
    {
        $permission = Permission::create(['name' => 'permission']);
        $this->user->givePermission($permission, $this->team);
        $this->other->givePermission($permission, $this->team);

        $this->assertRelated(Permission::with(['users', 'others'])->find($permission->id));
        $this->assertTrue(Permission::whereHas('others', fn (Builder $query) => $query->whereKey($this->other->id))->exists());
    }

    public function test_team_users_can_be_eager_loaded_and_queried(): void
    {
        // A team's users are the ones with a role in that team.
        $role = Role::create(['name' => 'role']);
        $this->user->addRole($role, $this->team);
        $this->other->addRole($role, $this->team);

        $this->assertRelated(Team::with(['users', 'others'])->find($this->team->id));
        $this->assertTrue(Team::whereHas('others', fn (Builder $query) => $query->whereKey($this->other->id))->exists());
        $this->assertFalse(Team::whereHas('users', fn (Builder $query) => $query->whereKey($this->user->id + 100))->exists());
    }

    protected function assertRelated(Model $model): void
    {
        $this->assertTrue($model->relationLoaded('users'));
        $this->assertTrue($model->relationLoaded('others'));
        $this->assertTrue($model->getRelation('users')->sole()->is($this->user));
        $this->assertTrue($model->getRelation('others')->sole()->is($this->other));
    }
}
