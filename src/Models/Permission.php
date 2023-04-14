<?php

declare(strict_types=1);

namespace Laratrust\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\Permission as PermissionContract;
use Laratrust\Traits\DynamicUserRelationshipCalls;

class Permission extends Model implements PermissionContract
{
    use DynamicUserRelationshipCalls;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Creates a new instance of the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('laratrust.tables.permissions');
    }

    protected static function booted(): void
    {
        static::deleting(function ($permission) {
            if (method_exists($permission, 'bootSoftDeletes') && ! $permission->forceDeleting) {
                return;
            }

            $permission->roles()->sync([]);

            foreach (array_keys(Config::get('laratrust.user_models')) as $key) {
                $permission->$key()->sync([]);
            }
        });
    }

    /**
     * Many-to-Many relations with role model.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Config::get('laratrust.models.role'),
            Config::get('laratrust.tables.permission_role'),
            Config::get('laratrust.foreign_keys.permission'),
            Config::get('laratrust.foreign_keys.role')
        );
    }

    /**
     * Morph by Many relationship between the permission and the one of the possible user models.
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany
    {
        return $this->morphedByMany(
            Config::get('laratrust.user_models')[$relationship],
            'user',
            Config::get('laratrust.tables.permission_user'),
            Config::get('laratrust.foreign_keys.permission'),
            Config::get('laratrust.foreign_keys.user')
        );
    }
}
