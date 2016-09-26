<?php

namespace Laratrust\Traits;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

use Illuminate\Support\Facades\Config;

trait LaratrustPermissionTrait
{
    /**
     * Many-to-Many relations with role model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            Config::get('laratrust.role'),
            Config::get('laratrust.permission_role_table'),
            Config::get('laratrust.permission_foreign_key'),
            Config::get('laratrust.role_foreign_key')
        );
    }

    /**
     * Boot the permission model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the permission model uses soft deletes.
     *
     * @return void|bool
     */
    public static function bootLaratrustPermissionTrait()
    {
        static::deleting(function ($permission) {
            if (!method_exists(Config::get('laratrust.permission'), 'bootSoftDeletes')) {
                $permission->roles()->sync([]);
            }
        });
    }
}
