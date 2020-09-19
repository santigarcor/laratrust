<?php

namespace Laratrust\Checkers\Role;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Checkers\PermissionAble\LaratrustPermissionAbleDefaultChecker;
use Laratrust\Models\LaratrustRole;

/**
 * Class LaratrustRoleDefaultChecker
 * @property Model|LaratrustRole $model
 */
class LaratrustRoleDefaultChecker extends LaratrustPermissionAbleDefaultChecker
{

}
