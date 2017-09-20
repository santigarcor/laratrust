<?php

namespace Laratrust\Tests\Models;

use Laratrust\Models\LaratrustRole;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends LaratrustRole
{
    use SoftDeletes;

    protected $guarded = [];
}
