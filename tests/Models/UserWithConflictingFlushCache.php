<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserWithConflictingFlushCache extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions, FlushesOwnCache {
        FlushesOwnCache::flushCache insteadof HasRolesAndPermissions;
    }

    protected $table = 'users';

    protected $guarded = [];
}
