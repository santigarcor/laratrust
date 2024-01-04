<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Models\Group as GroupModel;

class Group extends GroupModel
{
    use SoftDeletes;

    protected $guarded = [];
}
