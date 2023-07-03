<?php

namespace Laratrust\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\Team as TeamContract;
use Laratrust\Traits\DynamicUserRelationshipCalls;

class Team extends Model implements TeamContract
{
    use DynamicUserRelationshipCalls;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Creates a new instance of the model.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('laratrust.tables.teams');
    }

    /**
     * Boots the team model and adds event listener to
     * remove the many-to-many records when trying to delete.
     * It WON'T delete any records if the team model uses soft deletes.
     */
    protected static function booted(): void
    {
        static::deleting(function ($team) {
            if (method_exists($team, 'bootSoftDeletes') && ! $team->forceDeleting) {
                return;
            }

            foreach (array_keys(Config::get('laratrust.user_models')) as $key) {
                $team->$key()->sync([]);
            }
        });
    }

    public function getMorphByUserRelation(string $relationship): MorphToMany
    {
        return $this->morphedByMany(
            Config::get('laratrust.user_models')[$relationship],
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
