<?php

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use LaratrustUserTrait;
    use SoftDeletes;

    protected $guarded = [];
}
