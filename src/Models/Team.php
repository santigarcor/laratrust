<?php

declare(strict_types=1);

namespace Laratrust\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\Team as TeamContract;
use Laratrust\Support\ModelClasses;
use Laratrust\Support\PermissionRegistry;

/**
 * @property int|string $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Team extends Model implements TeamContract
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get the table from "laratrust.tables.teams", unless the model sets its own.
     */
    public function getTable(): string
    {
        return $this->table ?? Config::get('laratrust.tables.teams');
    }

    /**
     * Boots the team model and adds event listener to
     * remove the many-to-many records when trying to delete.
     * It WON'T delete any records if the team model uses soft deletes.
     */
    protected static function booted(): void
    {
        $forgetIds = fn () => app(PermissionRegistry::class)->forgetIds('team');
        static::saved($forgetIds);
        static::deleted($forgetIds);

        static::deleting(function (self $team): void {
            // Soft deleting keeps the assignments, so the model can be restored with them.
            if (method_exists($team, 'isForceDeleting') && ! $team->isForceDeleting()) {
                return;
            }

            foreach (ModelClasses::userRelationships() as $key) {
                $team->getMorphByUserRelation($key)->detach();
            }
        });
    }

    /**
     * @return MorphToMany<covariant Model, $this>
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany
    {
        return $this->morphedByMany(
            ModelClasses::user($relationship),
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.team'),
            Config::get('laratrust.foreign_keys.user')
        );
    }

    /**
     * Returns the team's foreign key.
     */
    public static function modelForeignKey(): string
    {
        return Config::get('laratrust.foreign_keys.team');
    }
}
