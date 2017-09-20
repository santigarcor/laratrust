<?php

namespace Laratrust\Tests\Models;

use Laratrust\Models\LaratrustTeam;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends LaratrustTeam
{
    use SoftDeletes;

    protected $guarded = [];
}
