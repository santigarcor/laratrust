<?php

declare(strict_types=1);

namespace Laratrust;

use BackedEnum;
use Illuminate\Contracts\Foundation\Application;
use Laratrust\Contracts\LaratrustUser;

/**
 * This class is the main entry point of laratrust. Usually this the interaction
 * with this class will be done through the Laratrust Facade.
 */
class Laratrust
{
    /**
     * Create a new confide instance.
     */
    public function __construct(public Application $app)
    {
    }

    /**
     * Checks if the current user has a role by its name.
     */
    public function hasRole(
        string|array|BackedEnum $role,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        if ($user = $this->user()) {
            return $user->hasRole($role, $team, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a permission by its name.
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        if ($user = $this->user()) {
            return $user->hasPermission($permission, $team, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a role or permission by its name.
     *
     * @param  array|string  $roles  The role(s) needed.
     * @param  array|string  $permissions  The permission(s) needed.
     * @param  array  $options  The Options.
     * @return bool
     */
    public function ability($roles, $permissions, $team = null, $options = [])
    {
        if ($user = $this->user()) {
            return $user->ability($roles, $permissions, $team, $options);
        }

        return false;
    }

    /**
     * Get the currently authenticated user or null.
     */
    protected function user(): ?LaratrustUser
    {
        return $this->app->auth->user();
    }
}
