<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laratrust\Models\Role as RoleModel;

class RoleWithCustomUsersRelation extends RoleModel
{
    protected $guarded = [];

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Team::class);
    }
}
