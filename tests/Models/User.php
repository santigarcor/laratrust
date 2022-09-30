<?php

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use LaratrustUserTrait;
    use SoftDeletes;

    protected $guarded = [];
}
