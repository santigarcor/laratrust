<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laratrust\Models\Role as RoleModel;

/**
 * Role model that overrides the dynamic users() relation with a
 * non many-to-many relation, see #679.
 */
class RoleWithCustomUsersRelation extends RoleModel
{
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id');
    }
}
