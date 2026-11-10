<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Models\Permission as PermissionModel;

#[Unguarded]
class Permission extends PermissionModel
{
    use SoftDeletes;
}
