<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    use SoftDeletes;

    protected $guarded = [];
}
