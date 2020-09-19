<?php

namespace Laratrust\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laratrust\Checkers\LaratrustCheckerManager;
use Laratrust\Contracts\Ownable;
use Laratrust\Helper;

trait LaratrustUserTrait
{
    use LaratrustHasEvents;
    use LaratrustHasRoleScopes;
    use LaratrustModelHasPermissions;

    /**
     * Boots the user model and attaches event listener to
     * remove the many-to-many records when trying to delete.
     * Will NOT delete any records if the user model uses soft deletes.
     *
     * @return void|bool
     */
    public static function bootLaratrustUserTrait()
    {

        static::deleting(function ($user) {
            if (method_exists($user, 'bootSoftDeletes') && !$user->forceDeleting) {
                return;
            }
            $user->roles()->sync([]);
        });
    }

    /**
     * Many-to-Many relations with Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        $roles = $this->morphToMany(
            Config::get('laratrust.models.role'),
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.role')
        );

        if (Config::get('laratrust.teams.enabled')) {
            $roles->withPivot(Config::get('laratrust.foreign_keys.team'));
        }

        return $roles;
    }

    /**
     * Many-to-Many relations with Team associated through the roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function rolesTeams()
    {
        if (!Config::get('laratrust.teams.enabled')) {
            return null;
        }

        return $this->morphToMany(
            Config::get('laratrust.models.team'),
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.team')
        )
            ->withPivot(Config::get('laratrust.foreign_keys.role'));
    }

    /**
     * Return the right checker for the user model.
     *
     * @return \Laratrust\Checkers\User\LaratrustUserChecker
     */
    protected function laratrustUserChecker()
    {
        return (new LaratrustCheckerManager($this))->getUserChecker();
    }

    /**
     * Get the the names of the user's roles.
     *
     * @param  string|bool  $team  Team name.
     * @return array
     */
    public function getRoles($team = null)
    {
        return $this->laratrustUserChecker()->getCurrentUserRoles($team);
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|array  $name  Role name or array of role names.
     * @param  string|bool  $team  Team name or requiredAll roles.
     * @param  bool  $requireAll  All roles in the array are required.
     * @return bool
     */
    public function hasRole($name, $team = null, $requireAll = false)
    {
        return $this->laratrustUserChecker()->currentUserHasRole(
            $name,
            $team,
            $requireAll
        );
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|array  $name  Role name or array of role names.
     * @param  string|bool  $team  Team name or requiredAll roles.
     * @param  bool  $requireAll  All roles in the array are required.
     * @return bool
     */
    public function isA($role, $team = null, $requireAll = false)
    {
        return $this->hasRole($role, $team, $requireAll);
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|array  $name  Role name or array of role names.
     * @param  string|bool  $team  Team name or requiredAll roles.
     * @param  bool  $requireAll  All roles in the array are required.
     * @return bool
     */
    public function isAn($role, $team = null, $requireAll = false)
    {
        return $this->hasRole($role, $team, $requireAll);
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array  $permission  Permission string or array of permissions.
     * @param  string|bool  $team  Team name or requiredAll roles.
     * @param  bool  $requireAll  All permissions in the array are required.
     * @return bool
     */
    public function hasPermission($permission, $team = null, $requireAll = false)
    {
        return $this->laratrustUserChecker()->currentModelHasPermission(
            $permission,
            $team,
            $requireAll
        );
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array  $permission  Permission string or array of permissions.
     * @param  string|bool  $team  Team name or requiredAll roles.
     * @param  bool  $requireAll  All permissions in the array are required.
     * @return bool
     */
    public function isAbleTo($permission, $team = null, $requireAll = false)
    {
        return $this->hasPermission($permission, $team, $requireAll);
    }

    /**
     * Checks role(s) and permission(s).
     *
     * @param  string|array  $roles  Array of roles or comma separated string
     * @param  string|array  $permissions  Array of permissions or comma separated string.
     * @param  string|bool  $team  Team name or requiredAll roles.
     * @param  array  $options  validate_all (true|false) or return_type (boolean|array|both)
     * @return array|bool
     * @throws \InvalidArgumentException
     */
    public function ability($roles, $permissions, $team = null, $options = [])
    {
        return $this->laratrustUserChecker()->currentUserHasAbility(
            $roles,
            $permissions,
            $team,
            $options
        );
    }


    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param  mixed  $role
     * @param  mixed  $team
     * @return static
     */
    public function attachRole($role, $team = null)
    {
        return $this->attachModel('roles', $role, $team);
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param  mixed  $role
     * @param  mixed  $team
     * @return static
     */
    public function detachRole($role, $team = null)
    {
        return $this->detachModel('roles', $role, $team);
    }

    /**
     * Attach multiple roles to a user.
     *
     * @param  mixed  $roles
     * @param  mixed  $team
     * @return static
     */
    public function attachRoles($roles = [], $team = null)
    {
        foreach ($roles as $role) {
            $this->attachRole($role, $team);
        }

        return $this;
    }

    /**
     * Detach multiple roles from a user.
     *
     * @param  mixed  $roles
     * @param  mixed  $team
     * @return static
     */
    public function detachRoles($roles = [], $team = null)
    {
        if (empty($roles)) {
            $roles = $this->roles()->get();
        }

        foreach ($roles as $role) {
            $this->detachRole($role, $team);
        }

        return $this;
    }

    /**
     * Sync roles to the user.
     *
     * @param  array  $roles
     * @param  mixed  $team
     * @param  boolean  $detaching
     * @return static
     */
    public function syncRoles($roles = [], $team = null, $detaching = true)
    {
        return $this->syncModels('roles', $roles, $team, $detaching);
    }

    /**
     * Sync roles to the user without detaching.
     *
     * @param  array  $roles
     * @param  mixed  $team
     * @return static
     */
    public function syncRolesWithoutDetaching($roles = [], $team = null)
    {
        return $this->syncRoles($roles, $team, false);
    }

    /**
     * Checks if the user owns the thing.
     *
     * @param  Object  $thing
     * @param  string|null  $foreignKeyName
     * @return boolean
     */
    public function owns($thing, string $foreignKeyName = null)
    {
        if ($thing instanceof Ownable) {
            $ownerKey = $thing->ownerKey($this);
        } else {
            $className = class_basename($this);
            $foreignKeyName = $foreignKeyName ?: Str::snake($className.'Id');
            $ownerKey = $thing->$foreignKeyName;
        }

        return $ownerKey == $this->getKey();
    }

    /**
     * Checks if the user has some role and if he owns the thing.
     *
     * @param  string|array  $role
     * @param  Object  $thing
     * @param  array  $options
     * @return boolean
     */
    public function hasRoleAndOwns($role, $thing, $options = [])
    {
        $options = Helper::checkOrSet('requireAll', $options, [false, true]);
        $options = Helper::checkOrSet('team', $options, [null]);
        $options = Helper::checkOrSet('foreignKeyName', $options, [null]);

        return $this->hasRole($role, $options['team'], $options['requireAll'])
            && $this->owns($thing, $options['foreignKeyName']);
    }

    /**
     * Checks if the user can do something and if he owns the thing.
     *
     * @param  string|array  $permission
     * @param  Object  $thing
     * @param  array  $options
     * @return boolean
     */
    public function isAbleToAndOwns($permission, $thing, $options = [])
    {
        $options = Helper::checkOrSet('requireAll', $options, [false, true]);
        $options = Helper::checkOrSet('foreignKeyName', $options, [null]);
        $options = Helper::checkOrSet('team', $options, [null]);

        return $this->hasPermission($permission, $options['team'], $options['requireAll'])
            && $this->owns($thing, $options['foreignKeyName']);
    }

    /**
     * Return all the user permissions.
     *
     * @param  null|array  $columns
     * @param  null  $team
     * @return \Illuminate\Support\Collection|static
     */
    public function allPermissions($columns = null, $team = null)
    {
        $columns = is_array($columns) ? $columns : null;
        if ($columns) {
            $columns[] = 'id';
            $columns = array_unique($columns);
        }


        $withColumns = $columns ? ":".implode(',', $columns) : '';

        $roles = $this->roles()->whereRelationTeamIs($team)->with("permissions{$withColumns}")->get();

        $roles = $roles->flatMap(function ($role) {
            return $role->permissions;
        });
        if (Config::get('laratrust.teams.enabled')) {

            $teams = $this->rolesTeams()->whereRelationTeamIs($team)->with("permissions{$withColumns}")->get();

            $teams = $teams->flatMap(function ($team) {
                return $team->permissions;
            });
        }

        return $this->permissions()->get($columns ?? ['*'])->merge($roles)->merge($teams ?? [])->unique('id');
    }

    /**
     * Flush the user's cache.
     *
     * @return void
     */
    public function flushCache()
    {
        return $this->laratrustUserChecker()->currentUserFlushCache();
    }

    /**
     * Handles the call to the magic methods with can,
     * like $user->isAbleToEditSomething().
     * @param  string  $method
     * @param  array  $parameters
     * @return boolean
     */
    private function handleMagicIsAbleTo($method, $parameters)
    {
        $case = str_replace('_case', '', Config::get('laratrust.magic_is_able_to_method_case'));
        $method = preg_replace('/^isAbleTo/', '', $method);

        if ($case == 'kebab') {
            $permission = Str::snake($method, '-');
        } else {
            $permission = Str::$case($method);
        }

        return $this->hasPermission($permission, array_shift($parameters), false);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (!preg_match('/^isAbleTo[A-Z].*/', $method)) {
            return parent::__call($method, $parameters);
        }

        return $this->handleMagicIsAbleTo($method, $parameters);
    }
}
