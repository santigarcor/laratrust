<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

class Other extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions;
    use SoftDeletes;

    protected $guarded = [];
}
