<?php

namespace Laratrust\Checkers;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Contracts\LaratrustCheckerInterface;

class QueryRolesAndPermissionsChecker implements LaratrustCheckerInterface
{
    public function modelHasRole(Model $model, $name, $team = null, $requireAll = false)
    {
    }

    public function modelHasPermission(Model $model, $permission, $team = null, $requireAll = false)
    {
    }

    public function modelHasAbility(Model $model, $roles, $permissions, $team = null, $options = [])
    {
    }

    public function modelFlushCache(Model $model)
    {
    }
}
