<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers;

use Closure;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laratrust\LaratrustFacade;
use Laratrust\Support\PermissionRegistry;
use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\Enums\Role as EnumsRole;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;

class PermissionRegistryTest extends LaratrustTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->app['config']->set('laratrust.cache.enabled', false);

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $team = Team::create(['name' => 'team_a']);

        foreach (['role_a', 'role_b', 'role_c'] as $index => $name) {
            Role::create(['name' => $name])->givePermissions([
                Permission::create(['name' => "{$name}.permission_a"]),
                Permission::create(['name' => "{$name}.permission_b"]),
            ]);
            $this->user->addRole($name, $index === 2 ? $team : null);
        }

        $this->user->givePermission(Permission::create(['name' => 'direct']), $team);
    }

    public function test_checks_load_everything_with_four_queries_and_are_memoized_for_the_request(): void
    {
        $queries = $this->countQueries(function (): void {
            $this->assertTrue($this->user->hasRole('role_a'));
            $this->assertTrue($this->user->isAbleTo('role_c.permission_b', $this->user->rolesTeams->first()));
            $this->assertTrue($this->user->isAbleTo(['role_a.permission_a', 'role_b.*', 'direct'], requireAll: true));
            $this->assertFalse($this->user->isAbleTo('missing'));
            $this->assertSame(['role_a', 'role_b', 'role_c'], $this->user->getRoles());
        });

        // roles + direct permissions + the roles with their permissions eager loaded (rolesTeams is one more).
        $this->assertSame(5, $queries);

        $this->assertSame(0, $this->countQueries(function (): void {
            foreach (range(1, 50) as $ignored) {
                $this->user->isAbleTo(['role_a.permission_a', 'role_c.permission_b', 'direct']);
            }
        }));
    }

    public function test_only_the_needed_columns_are_selected(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->user->isAbleTo('role_a.permission_a');

        $this->assertNotEmpty($queries);
        foreach ($queries as $sql) {
            $this->assertStringNotContainsString('*', $sql);
        }
    }

    public function test_the_persistent_cache_is_used_across_requests(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);

        $this->assertSame(4, $this->countQueries(fn (): bool => $this->user->hasPermission('role_b.permission_a')));

        $this->simulateNewRequest();

        $this->assertSame(0, $this->countQueries(fn (): bool => $this->user->hasPermission('role_b.permission_a')));
        $this->assertTrue(Cache::has($this->user->laratrustCacheKey()));
    }

    public function test_the_cache_works_when_objects_cannot_be_unserialized(): void
    {
        // The default in Laravel 13 apps: no classes are unserialized from the cache.
        $this->app['config']->set('cache.serializable_classes', false);
        $this->app['config']->set('cache.stores.serialized', ['driver' => 'array', 'serialize' => true]);
        $this->app['config']->set('cache.default', 'serialized');
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->simulateNewRequest();

        $this->assertTrue($this->user->hasPermission('role_b.permission_a'));

        $this->simulateNewRequest();

        $this->assertSame(0, $this->countQueries(function (): void {
            $this->assertTrue($this->user->hasPermission('role_b.permission_a'));
            $this->assertTrue($this->user->hasPermission('direct'));
        }));
    }

    public function test_invalid_cached_assignments_are_treated_as_a_cache_miss(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        Cache::put($this->user->laratrustCacheKey(), ['roles' => [['team' => null]], 'permissions' => []]);

        $this->assertTrue($this->user->hasRole('role_b'));
        $this->assertSame(
            Role::whereIn('name', ['role_a', 'role_b', 'role_c'])->pluck('id')->all(),
            array_column(Cache::get($this->user->laratrustCacheKey())['roles'], 'id'),
        );
    }

    public function test_invalid_cached_role_permissions_are_treated_as_a_cache_miss(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $roleA = Role::where('name', 'role_a')->first();
        Cache::put($roleA->laratrustCacheKey(), ['name' => 'role_a', 'permissions' => ['role_a.permission_a', 42]]);

        $this->assertTrue($this->user->isAbleTo('role_a.permission_b'));
        $this->assertSame(
            ['name' => 'role_a', 'permissions' => ['role_a.permission_a', 'role_a.permission_b']],
            Cache::get($roleA->laratrustCacheKey()),
        );
    }

    public function test_a_role_deleted_after_being_cached_has_no_permissions(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->isAbleTo('role_b.permission_a'));

        // The user's assignments are still cached, but the role is gone and its cache expired.
        $roleB = Role::where('name', 'role_b')->first();
        Cache::forget($roleB->laratrustCacheKey());
        DB::table('roles')->where('id', $roleB->id)->delete();
        $this->simulateNewRequest();

        $this->assertFalse($this->user->isAbleTo('role_b.permission_a'));
        $this->assertTrue($this->user->isAbleTo('role_a.permission_a'));
        $this->assertSame(['name' => null, 'permissions' => []], Cache::get($roleB->laratrustCacheKey()));
        $this->assertSame(0, $this->countQueries(fn (): bool => $this->user->isAbleTo('role_b.permission_b')));
    }

    public function test_renaming_a_role_refreshes_the_users_that_have_it(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->hasRole('role_a'));

        $this->simulateNewRequest();
        Role::where('name', 'role_a')->first()->update(['name' => 'renamed']);
        $this->simulateNewRequest();

        $this->assertFalse($this->user->hasRole('role_a'));
        $this->assertTrue($this->user->hasRole('renamed'));
        $this->assertSame(['renamed', 'role_b', 'role_c'], $this->user->getRoles());
        $this->assertTrue(Cache::has($this->user->laratrustCacheKey()));
    }

    public function test_deleting_a_role_refreshes_the_users_that_had_it(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->hasRole(['role_a', 'role_b'], requireAll: true));

        $this->simulateNewRequest();
        Role::where('name', 'role_a')->first()->delete();
        Role::where('name', 'role_b')->first()->forceDelete();
        $this->simulateNewRequest();

        $this->assertFalse($this->user->hasRole('role_a'));
        $this->assertFalse($this->user->isAbleTo('role_a.permission_a'));
        $this->assertFalse($this->user->hasRole('role_b'));
        $this->assertSame(['role_c'], $this->user->getRoles());

        Role::withTrashed()->where('name', 'role_a')->first()->restore();
        $this->simulateNewRequest();
        $this->assertTrue($this->user->hasRole('role_a'));
    }

    public function test_the_memoized_data_can_be_reset(): void
    {
        $this->assertFalse($this->user->hasRole('role_d'));

        $this->user->roles()->attach(Role::create(['name' => 'role_d'])->id);
        $this->assertFalse($this->user->hasRole('role_d'));

        LaratrustFacade::reset();
        $this->assertTrue($this->user->hasRole('role_d'));
    }

    public function test_the_memoized_data_does_not_survive_the_request(): void
    {
        $this->assertFalse($this->user->hasRole('role_d'));

        // Bypassing Laratrust leaves the memoized data stale for the rest of the request...
        $roleD = Role::create(['name' => 'role_d']);
        $this->user->roles()->attach($roleD->id);
        $this->assertFalse($this->user->hasRole('role_d'));

        // ...but it is discarded between requests and queue jobs.
        $this->simulateNewRequest();
        $this->assertTrue($this->user->hasRole('role_d'));
    }

    public function test_flushing_the_user_refreshes_the_memoized_data(): void
    {
        $this->assertFalse($this->user->hasRole('role_d'));

        $this->user->roles()->attach(Role::create(['name' => 'role_d'])->id);
        $this->user->flushLaratrustCache();

        $this->assertTrue($this->user->hasRole('role_d'));
    }

    public function test_changing_a_role_refreshes_the_users_that_have_it(): void
    {
        $this->assertFalse($this->user->isAbleTo('new_permission'));

        Role::where('name', 'role_a')->first()->givePermission(Permission::create(['name' => 'new_permission']));

        $this->assertTrue($this->user->isAbleTo('new_permission'));
    }

    public function test_team_names_are_resolved_once_per_request(): void
    {
        $teamQueries = 0;
        DB::listen(function ($query) use (&$teamQueries): void {
            $teamQueries += str_contains($query->sql, 'from '.DB::getQueryGrammar()->wrapTable('teams')) ? 1 : 0;
        });

        $this->user->hasRole('role_c', 'team_a');
        $this->user->isAbleTo('direct', 'team_a');
        $this->user->getRoles('team_a');

        $this->assertSame(1, $teamQueries);
    }

    public function test_names_are_turned_into_a_list_of_strings(): void
    {
        $registry = app(PermissionRegistry::class);

        $this->assertSame(['admin'], $registry->names('admin'));
        $this->assertSame(['admin', 'editor'], $registry->names('admin|editor'));
        $this->assertSame(['role_a'], $registry->names(EnumsRole::ROLE_A));
        $this->assertSame(['role_a', 'editor'], $registry->names([EnumsRole::ROLE_A, 'editor']));
        $this->assertSame(['permission_a', 'permission_b'], $registry->names(['x' => EnumsPermission::PERM_A, 'y' => 'permission_b']));
        $this->assertSame([], $registry->names([]));
    }

    public function test_keys_are_resolved_from_every_supported_value(): void
    {
        $registry = app(PermissionRegistry::class);
        $role = Role::where('name', 'role_a')->first();
        $uuid = Str::uuid();

        $this->assertSame($role->id, $registry->idFor($role, 'role'));
        $this->assertSame($role->id, $registry->idFor('role_a', 'role'));
        $this->assertSame($role->id, $registry->idFor(['id' => $role->id], 'role'));
        $this->assertSame(5, $registry->idFor(5, 'role'));
        $this->assertSame($uuid->toString(), $registry->idFor($uuid, 'role'));
        $this->assertNull($registry->idFor(null, 'role'));
    }

    public function test_renaming_a_team_forgets_the_resolved_names(): void
    {
        $this->assertTrue($this->user->hasRole('role_c', 'team_a'));

        Team::where('name', 'team_a')->first()->update(['name' => 'team_b']);
        Team::create(['name' => 'team_a']);

        $this->assertFalse($this->user->hasRole('role_c', 'team_a'));
        $this->assertTrue($this->user->hasRole('role_c', 'team_b'));
    }

    public function test_renaming_a_permission_refreshes_its_roles_and_users(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->isAbleTo('role_a.permission_a'));
        $this->assertTrue($this->user->isAbleTo('direct'));

        Permission::where('name', 'role_a.permission_a')->first()->update(['name' => 'renamed']);
        Permission::where('name', 'direct')->first()->update(['name' => 'renamed_direct']);

        $this->assertTrue($this->user->isAbleTo('renamed'));
        $this->assertTrue($this->user->isAbleTo('renamed_direct'));

        $this->simulateNewRequest();
        $this->assertFalse($this->user->isAbleTo('role_a.permission_a'));
        $this->assertFalse($this->user->isAbleTo('direct'));
        $this->assertTrue($this->user->isAbleTo('renamed'));
        $this->assertTrue($this->user->isAbleTo('renamed_direct'));
    }

    public function test_changing_other_permission_columns_keeps_the_cache(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->isAbleTo('role_a.permission_a'));
        $roleA = Role::where('name', 'role_a')->first();

        Permission::where('name', 'role_a.permission_a')->first()->update(['display_name' => 'Permission A']);
        Permission::create(['name' => 'new_permission']);

        $this->assertTrue(Cache::has($roleA->laratrustCacheKey()));
        $this->assertTrue(Cache::has($this->user->laratrustCacheKey()));
    }

    public function test_soft_deleting_and_restoring_a_permission_refreshes_its_roles_and_users(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->isAbleTo('role_a.permission_a'));
        $this->assertTrue($this->user->isAbleTo('direct'));
        $roleAPermission = Permission::where('name', 'role_a.permission_a')->first();
        $direct = Permission::where('name', 'direct')->first();

        $roleAPermission->delete();
        $direct->delete();
        $this->simulateNewRequest();
        $this->assertFalse($this->user->isAbleTo('role_a.permission_a'));
        $this->assertFalse($this->user->isAbleTo('direct'));

        $roleAPermission->restore();
        $direct->restore();
        $this->simulateNewRequest();
        $this->assertTrue($this->user->isAbleTo('role_a.permission_a'));
        $this->assertTrue($this->user->isAbleTo('direct'));
    }

    public function test_force_deleting_a_permission_refreshes_its_roles_and_users(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->isAbleTo('role_a.permission_a'));
        $this->assertTrue($this->user->isAbleTo('direct'));

        Permission::where('name', 'role_a.permission_a')->first()->forceDelete();
        Permission::where('name', 'direct')->first()->forceDelete();
        $this->simulateNewRequest();

        $this->assertFalse($this->user->isAbleTo('role_a.permission_a'));
        $this->assertFalse($this->user->isAbleTo('direct'));
        $this->assertTrue($this->user->isAbleTo('role_a.permission_b'));
    }

    public function test_force_deleting_a_team_removes_the_assignments_within_it(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->assertTrue($this->user->hasRole('role_c'));
        $this->assertTrue($this->user->isAbleTo('direct'));

        Team::where('name', 'team_a')->first()->forceDelete();
        $this->simulateNewRequest();

        $this->assertFalse($this->user->hasRole('role_c'));
        $this->assertFalse($this->user->isAbleTo('direct'));
        $this->assertTrue($this->user->hasRole('role_a'));
        $this->assertSame(0, DB::table('role_user')->whereNotNull('team_id')->count());
        $this->assertSame(0, DB::table('permission_user')->whereNotNull('team_id')->count());
    }

    public function test_a_user_model_extending_another_one_has_its_own_cache_key(): void
    {
        $this->app['config']->set('laratrust.user_models', ['users' => User::class, 'admins' => AdminUser::class]);

        $this->assertSame("laratrust_user_users_{$this->user->id}", $this->user->laratrustCacheKey());
        $this->assertSame("laratrust_user_admins_{$this->user->id}", AdminUser::find($this->user->id)->laratrustCacheKey());
        // A subclass that isn't configured uses its parent's key.
        $this->assertSame("laratrust_user_users_{$this->user->id}", UnlistedUser::find($this->user->id)->laratrustCacheKey());
    }

    public function test_the_cache_keys_can_be_customized(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->app['config']->set('laratrust.user_models.tenant_users', TenantUser::class);
        $this->app['config']->set('laratrust.models.role', TenantRole::class);
        $user = TenantUser::find($this->user->id);
        $role = TenantRole::where('name', 'role_a')->first();

        $this->assertTrue($user->isAbleTo('role_a.permission_a'));
        $this->assertTrue($role->hasPermission('role_a.permission_a'));

        $this->assertTrue(Cache::has("tenant_1_user_{$user->id}"));
        $this->assertTrue(Cache::has("tenant_1_role_{$role->id}"));
        $this->assertFalse(Cache::has("laratrust_user_users_{$user->id}"));

        $user->flushLaratrustCache();
        $role->flushLaratrustCache();

        $this->assertFalse(Cache::has("tenant_1_user_{$user->id}"));
        $this->assertFalse(Cache::has("tenant_1_role_{$role->id}"));
    }

    public function test_many_direct_permissions_are_checked_quickly(): void
    {
        $teams = collect(range(1, 40))->map(fn ($i) => Team::create(['name' => "team_{$i}"]));
        $now = now();
        $permissions = collect(range(1, 200))->map(fn ($i): array => [
            'name' => "permission_{$i}", 'created_at' => $now, 'updated_at' => $now,
        ]);
        Permission::insert($permissions->all());
        $permissionIds = Permission::where('name', 'like', 'permission_%')->pluck('id');

        // 40 teams x 200 permissions = 8000 rows in permission_user, as reported in #645.
        foreach ($teams as $team) {
            DB::table('permission_user')->insert($permissionIds->map(fn ($id): array => [
                'permission_id' => $id,
                'user_id' => $this->user->id,
                'user_type' => $this->user->getMorphClass(),
                'team_id' => $team->id,
            ])->all());
        }
        $this->user->flushLaratrustCache();

        $start = microtime(true);
        foreach ($teams as $team) {
            $this->assertTrue($this->user->isAbleTo(['permission_1', 'permission_200'], $team, true));
            $this->assertFalse($this->user->isAbleTo(['permission_1', 'missing'], $team, true));
        }
        $elapsed = microtime(true) - $start;

        // Previously each of these checks scanned all 8000 rows, taking seconds in total.
        $this->assertLessThan(1.0, $elapsed);
    }

    public function test_a_role_id_shared_by_two_tenants_does_not_leak_permissions(): void
    {
        $this->app['config']->set('laratrust.cache.enabled', true);
        $roleId = Role::where('name', 'role_a')->value('id');

        // Same role id, different tenants: each tenant caches its own permissions for it.
        Cache::put("tenant_a_role_{$roleId}", ['name' => 'role_a', 'permissions' => ['tenant_a.permission']]);
        Cache::put("tenant_b_role_{$roleId}", ['name' => 'role_a', 'permissions' => ['tenant_b.permission']]);

        $this->app['config']->set('laratrust.models.role', TenantARole::class);
        $this->assertTrue($this->user->isAbleTo('tenant_a.permission'));
        $this->assertFalse($this->user->isAbleTo('tenant_b.permission'));

        // Same process, no reset: switch tenant and check a different user record of that tenant.
        $this->app['config']->set('laratrust.models.role', TenantBRole::class);
        $this->app['config']->set('laratrust.user_models.tenant_b_users', TenantBUser::class);
        $tenantBUser = TenantBUser::find($this->user->id);

        $this->assertTrue($tenantBUser->isAbleTo('tenant_b.permission'));
        $this->assertFalse($tenantBUser->isAbleTo('tenant_a.permission'));
    }

    public function test_role_permissions_are_shared_between_users_instead_of_copied(): void
    {
        $small = $this->memoryPerUserWithARoleOf(10, 'small');
        $large = $this->memoryPerUserWithARoleOf(500, 'large');

        // If each user copied its role's permissions, a 50x bigger role would cost ~50x more per user.
        $this->assertLessThan($small * 1.5, $large);
    }

    protected function memoryPerUserWithARoleOf(int $permissionsCount, string $prefix): float
    {
        $this->app->forgetScopedInstances();
        $role = Role::create(['name' => "{$prefix}_role"]);
        $now = now();
        Permission::insert(collect(range(1, $permissionsCount))->map(fn ($i): array => [
            'name' => "{$prefix}.permission_{$i}", 'created_at' => $now, 'updated_at' => $now,
        ])->all());
        $role->givePermissions(Permission::where('name', 'like', "{$prefix}.%")->get());

        $teams = collect(range(1, 3))->map(fn ($i) => Team::create(['name' => "{$prefix}_team_{$i}"]));
        $users = collect(range(1, 300))->map(fn ($i) => User::create(['name' => "{$prefix}{$i}", 'email' => "{$prefix}{$i}@test.com"]));
        DB::table('role_user')->insert($users->crossJoin($teams)->map(fn ($pair): array => [
            'role_id' => $role->id,
            'user_id' => $pair[0]->id,
            'user_type' => $pair[0]->getMorphClass(),
            'team_id' => $pair[1]->id,
        ])->all());

        // Load the shared role set first, so only the per-user cost is measured.
        $this->assertTrue($users->first()->isAbleTo("{$prefix}.permission_1", $teams[0]));

        gc_collect_cycles();
        $before = memory_get_usage();
        foreach ($users->skip(1) as $user) {
            $this->assertTrue($user->isAbleTo("{$prefix}.permission_{$permissionsCount}", $teams[0]));
        }
        gc_collect_cycles();

        return (memory_get_usage() - $before) / ($users->count() - 1);
    }

    protected function countQueries(Closure $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $callback();

        DB::getEventDispatcher()->forget(QueryExecuted::class);

        return $count;
    }

    protected function simulateNewRequest(): void
    {
        $this->app->forgetScopedInstances();
        $this->user = User::find($this->user->id);
    }
}

#[Table(name: 'users')]
class TenantUser extends User
{
    public function getMorphClass(): string
    {
        return User::class;
    }

    public function laratrustCacheKey(): string
    {
        return "tenant_1_user_{$this->getKey()}";
    }
}

class TenantRole extends Role
{
    public function laratrustCacheKey(): string
    {
        return "tenant_1_role_{$this->getKey()}";
    }
}

#[Table(name: 'roles')]
class TenantARole extends Role
{
    public function laratrustCacheKey(): string
    {
        return "tenant_a_role_{$this->getKey()}";
    }
}

#[Table(name: 'roles')]
class TenantBRole extends Role
{
    public function laratrustCacheKey(): string
    {
        return "tenant_b_role_{$this->getKey()}";
    }
}

#[Table(name: 'users')]
class TenantBUser extends User
{
    public function getMorphClass(): string
    {
        return User::class;
    }

    public function laratrustCacheKey(): string
    {
        return "tenant_b_user_{$this->getKey()}";
    }
}

#[Table(name: 'users')]
class AdminUser extends User {}

#[Table(name: 'users')]
class UnlistedUser extends User {}
