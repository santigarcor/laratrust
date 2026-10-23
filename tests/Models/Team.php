<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Models\Team as TeamModel;

#[Unguarded]
class Team extends TeamModel
{
    use SoftDeletes;
}
