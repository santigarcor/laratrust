<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

class UserWithConflictingFlushCache extends Authenticatable implements LaratrustUser
{
    use FlushesOwnCache, HasRolesAndPermissions {
        FlushesOwnCache::flushCache insteadof HasRolesAndPermissions;
    }

    protected $table = 'users';

    protected $guarded = [];
}
