<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laratrust\Models\Permission as PermissionModel;

/**
 * Permission model that overrides the dynamic users() relation with a
 * non many-to-many relation, see #679.
 */
class PermissionWithCustomUsersRelation extends PermissionModel
{
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id');
    }
}
