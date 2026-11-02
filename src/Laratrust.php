<?php

declare(strict_types=1);

namespace Laratrust;

use BackedEnum;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Foundation\Application;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Support\PermissionRegistry;

/**
 * Checks for the authenticated user, used by the Laratrust facade and the Blade directives.
 * Without an authenticated user, every check is false.
 */
class Laratrust
{
    /**
     * Create a new Laratrust instance.
     */
    public function __construct(public Application $app)
    {
    }

    /**
     * Check if the current user has a role by its name.
     *
     * @param string|array<string|BackedEnum>|BackedEnum $role
     */
    public function hasRole(
        string|array|BackedEnum $role,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        if (($user = $this->user()) instanceof LaratrustUser) {
            return $user->hasRole($role, $team, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a permission by its name.
     *
     * @param string|array<string|BackedEnum>|BackedEnum $permission
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        if (($user = $this->user()) instanceof LaratrustUser) {
            return $user->hasPermission($permission, $team, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user does not have a permission by its name.
     *
     * @param string|array<string|BackedEnum>|BackedEnum $permission
     */
    public function doesntHavePermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return !$this->hasPermission($permission, $team, $requireAll);
    }

    /**
     * Check if the current user has a permission by its name.
     * Alias to hasPermission.
     *
     * @param string|array<string|BackedEnum>|BackedEnum $permission
     */
    public function isAbleTo(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->hasPermission($permission, $team, $requireAll);
    }

    /**
     * Check if the current user does not have a permission by its name.
     * Alias to doesntHavePermission.
     *
     * @param string|array<string|BackedEnum>|BackedEnum $permission
     */
    public function isNotAbleTo(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->doesntHavePermission($permission, $team, $requireAll);
    }

    /**
     * Check if the current user has a role or permission by its name.
     *
     * @param string|array<string|BackedEnum>|BackedEnum                         $roles
     * @param string|array<string|BackedEnum>|BackedEnum                         $permissions
     * @param array{validate_all?: bool, return_type?: 'boolean'|'array'|'both'} $options
     *
     * @return bool|array{roles: array<string, bool>, permissions: array<string, bool>}|array{bool, array{roles: array<string, bool>, permissions: array<string, bool>}}
     */
    public function ability(string|array|BackedEnum $roles, string|array|BackedEnum $permissions, mixed $team = null, array $options = []): bool|array
    {
        if (($user = $this->user()) instanceof LaratrustUser) {
            return $user->ability($roles, $permissions, $team, $options);
        }

        return false;
    }

    /**
     * Forget the roles and permissions loaded in this process, so the next checks read them again.
     *
     * Laravel already does it between requests and queue jobs. Call it in long-running processes
     * that don't, e.g. a daemon command, after the roles or permissions changed.
     */
    public function reset(): void
    {
        $this->app->make(PermissionRegistry::class)->reset();
    }

    /**
     * Get the currently authenticated user or null.
     */
    protected function user(): ?LaratrustUser
    {
        $user = $this->app->make(AuthFactory::class)->guard()->user();

        return $user instanceof LaratrustUser ? $user : null;
    }
}
