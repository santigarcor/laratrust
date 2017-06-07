<?php

namespace Laratrust;

/**
 * This class is the main entry point of laratrust. Usually this the interaction
 * with this class will be done through the Laratrust Facade
 *
 * @license MIT
 * @package Laratrust
 */

class Laratrust
{
    /**
     * Laravel application
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;

    /**
     * Create a new confide instance.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Checks if the current user has a role by its name
     *
     * @param string $name Role name.
     *
     * @return bool
     */
    public function hasRole($role, $requireAll = false)
    {
        if ($user = $this->user()) {
            return $user->hasRole($role, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a permission by its name
     *
     * @param string $permission Permission string.
     *
     * @return bool
     */
    public function can($permission, $requireAll = false)
    {
        if ($user = $this->user()) {
            return $user->hasPermission($permission, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a role or permission by its name
     *
     * @param array|string $roles            The role(s) needed.
     * @param array|string $permissions      The permission(s) needed.
     * @param array $options                 The Options.
     *
     * @return bool
     */
    public function ability($roles, $permissions, $options = [])
    {
        if ($user = $this->user()) {
            return $user->ability($roles, $permissions, $options);
        }

        return false;
    }

    /**
     * Checks if the user owns the thing
     * @param  Object $thing
     * @param  string $foreignKeyName
     * @return boolean
     */
    public function owns($thing, $foreignKeyName = null)
    {
        if ($user = $this->user()) {
            return $user->owns($thing, $foreignKeyName);
        }

        return false;
    }

    /**
     * Checks if the user has some role and if he owns the thing
     * @param  string|array $role
     * @param  Object $thing
     * @param  array  $options
     * @return boolean
     */
    public function hasRoleAndOwns($role, $thing, $options = [])
    {
        if ($user = $this->user()) {
            return $user->hasRoleAndOwns($role, $thing, $options);
        }

        return false;
    }

    /**
     * Checks if the user can do something and if he owns the thing
     * @param  string|array $permission
     * @param  Object $thing
     * @param  array  $options
     * @return boolean
     */
    public function canAndOwns($permission, $thing, $options = [])
    {
        if ($user = $this->user()) {
            return $user->canAndOwns($permission, $thing, $options);
        }

        return false;
    }

    /**
     * Get the currently authenticated user or null.
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function user()
    {
        return $this->app->auth->user();
    }

    /**
     * Checks if the user has a level equal to or greater than supplied
     * @param  int $level
     * @return boolean
     */
    public function hasLevelOrGreater($level)
    {
        if ($user = $this->user()) {
            return $user->hasLevelOrGreater($level);
        }
        return false;
    }

    /**
     * Checks if the user has a level equal to or less than supplied
     * @param  int $level
     * @return boolean
     */
    public function hasLevelOrLess($level)
    {
        if ($user = $this->user()) {
            return $user->hasLevelOrLess($level);
        }
        return false;
    }

    /**
     * Checks if the user has a level equal to or less than supplied
     * @param  string $levels
     * @return boolean
     */
    public function hasLevelBetween($levels)
    {
        if ($user = $this->user()) {
            return $user->hasLevelBetween($levels);
        }
        return false;
    }
}
