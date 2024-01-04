<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasGroupsAndRolesAndPermissions;

class Other extends Model implements LaratrustUser
{
    use HasGroupsAndRolesAndPermissions;
    use SoftDeletes;

    protected $guarded = [];
}
