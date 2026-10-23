<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

#[Unguarded]
class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions;
    use SoftDeletes;
}
