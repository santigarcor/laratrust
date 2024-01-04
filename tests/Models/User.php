<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasGroupsAndRolesAndPermissions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements LaratrustUser
{
    use HasGroupsAndRolesAndPermissions;
    use SoftDeletes;

    protected $guarded = [];
}
