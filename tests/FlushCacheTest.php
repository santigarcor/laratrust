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

    public function test_laratrust_cache_is_flushed_when_another_trait_owns_flush_cache(): void
    {
        $user = UserWithConflictingFlushCache::create(['name' => 'test', 'email' => 'test@test.com']);
        Role::create(['name' => 'role_a']);

        $this->assertFalse($user->hasRole('role_a'));
        $this->assertTrue(Cache::has("laratrust_user_conflicting_users_{$user->id}"));

        $user->addRole('role_a');

        $this->assertFalse(Cache::has("laratrust_user_conflicting_users_{$user->id}"));
        $this->assertTrue($user->hasRole('role_a'));
        $this->assertFalse($user->ownCacheFlushed);
    }

    public function test_laratrust_models_no_longer_define_flush_cache(): void
    {
        // Only flushLaratrustCache() exists, so third party flushCache() methods never collide (#706).
        $this->assertFalse(method_exists(User::class, 'flushCache'));
        $this->assertFalse(method_exists(Role::class, 'flushCache'));
        $this->assertTrue(method_exists(User::class, 'flushLaratrustCache'));
        $this->assertTrue(method_exists(Role::class, 'flushLaratrustCache'));
    }
}
