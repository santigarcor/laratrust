<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Models\Role as RoleModel;

#[Unguarded]
class Role extends RoleModel
{
    use SoftDeletes;
}
