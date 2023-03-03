<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Models\Permission as PermissionModel;

class Permission extends PermissionModel
{
    use SoftDeletes;

    protected $guarded = [];
}
