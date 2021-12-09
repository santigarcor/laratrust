<?php

namespace Laratrust\Contracts;

use Illuminate\Database\Eloquent\Model;

interface LaratrustUserCheckerInterface
{
    public function __construct(Model $user);

    /**
     * @param string|string[] $name
     * @param string|null $team
     * @param bool $requireAll
     * @return bool
     */
    public function currentUserHasRole($name, $team = null, $requireAll = false);

    /**
     * @param string|string[] $permission
     * @param string|null $team
     * @param bool $requireAll
     * @return bool
     */
    public function currentUserHasPermission($permission, $team = null, $requireAll = false);

    /**
     * Checks role(s) and permission(s).
     *
     * @param  string|string[]  $roles       Array of roles or comma separated string
     * @param  string|string[]  $permissions Array of permissions or comma separated string.
     * @param  string|bool  $team      Team name or requiredAll roles.
     * @param  array  $options     validate_all (true|false) or return_type (boolean|array|both)
     * @throws \InvalidArgumentException
     * @return array|bool
     */
    public function currentUserHasAbility($roles, $permissions, $team = null, $options = []);

    /**
     * @return void
     */
    public function currentUserFlushCache();
}