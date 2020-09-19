<?php

namespace Laratrust\Checkers\Role;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Checkers\PermissionAble\LaratrustPermissionAbleQueryChecker;
use Laratrust\Models\LaratrustRole;

/**
 * Class LaratrustRoleQueryChecker
 * @property Model|LaratrustRole $model
 */
class LaratrustRoleQueryChecker extends LaratrustPermissionAbleQueryChecker
{

}
