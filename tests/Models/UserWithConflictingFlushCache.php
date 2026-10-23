<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

class UserWithConflictingFlushCache extends Authenticatable implements LaratrustUser
{
    // Laratrust no longer defines flushCache(), so both traits can be used without "insteadof" (#706).
    use FlushesOwnCache;
    use HasRolesAndPermissions;

    protected $table = 'users';

    protected $guarded = [];
}
