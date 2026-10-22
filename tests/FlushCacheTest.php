<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Support\Facades\Cache;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\Models\UserWithConflictingFlushCache;

class FlushCacheTest extends LaratrustTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->app['config']->set('laratrust.cache.enabled', true);
        $this->app['config']->set('laratrust.user_models.conflicting_users', UserWithConflictingFlushCache::class);
    }

    public function testLaratrustCacheIsFlushedWhenAnotherTraitOwnsFlushCache()
    {
        $user = UserWithConflictingFlushCache::create(['name' => 'test', 'email' => 'test@test.com']);
        Role::create(['name' => 'role_a']);

        $this->assertFalse($user->hasRole('role_a'));
        $this->assertTrue(Cache::has("laratrust_roles_for_conflicting_users_{$user->id}"));

        $user->addRole('role_a');

        $this->assertFalse(Cache::has("laratrust_roles_for_conflicting_users_{$user->id}"));
        $this->assertTrue($user->hasRole('role_a'));
        $this->assertFalse($user->ownCacheFlushed);
    }

    public function testDeprecatedFlushCacheStillFlushesLaratrustCache()
    {
        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $role = Role::create(['name' => 'role_a']);

        $user->hasRole('role_a');
        $role->hasPermission('permission_a');
        $this->assertTrue(Cache::has("laratrust_roles_for_users_{$user->id}"));
        $this->assertTrue(Cache::has("laratrust_permissions_for_role_{$role->id}"));

        $user->flushCache();
        $role->flushCache();

        $this->assertFalse(Cache::has("laratrust_roles_for_users_{$user->id}"));
        $this->assertFalse(Cache::has("laratrust_permissions_for_role_{$role->id}"));
    }
}
