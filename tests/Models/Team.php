<?php

namespace Laratrust\Tests\Models;

use Laratrust\Models\Team as TeamModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends TeamModel
{
    use SoftDeletes;

    protected $guarded = [];
}
