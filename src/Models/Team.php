<?php

declare(strict_types=1);

namespace Laratrust\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Contracts\Team as TeamContract;
use Laratrust\Support\ModelClasses;
use Laratrust\Support\PermissionRegistry;

/**
 * @property int|string  $id
 * @property string      $name
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
     * remove the many-to-many records when trying to force delete.
     * It WON'T delete any records when the team is soft deleted.
     */
    protected static function booted(): void
    {
        $forgetIds = fn () => app(PermissionRegistry::class)->forgetIds('team');
        static::saved($forgetIds);
        static::deleted($forgetIds);

        static::deleting(function (self $team): void {
            // Soft deleting keeps the assignments, so the model can be restored with them.
            if (method_exists($team, 'isForceDeleting') && !$team->isForceDeleting()) {
                return;
            }

            // Before the pivot rows are detached, as they tell which users have assignments in the team.
            $team->flushLaratrustCache();

            foreach (ModelClasses::userRelationships() as $key) {
                $team->getMorphByUserRelation($key)->detach();
                $team->getMorphByUserPermissionRelation($key)->detach();
            }
        });
    }

    /**
     * Flush the cache of the users that have roles or permissions within the team.
     *
     * They're loaded with only their key, which is all laratrustCacheKey() uses by default.
     */
    public function flushLaratrustCache(): void
    {
        foreach (ModelClasses::userRelationships() as $key) {
            $user = new (ModelClasses::user($key));
            $roleUserTeam = $user->roles()->qualifyPivotColumn(static::modelForeignKey());
            $permissionUserTeam = $user->permissions()->qualifyPivotColumn(static::modelForeignKey());

            // Without the role's global scopes: the users' cache keeps their soft-deleted roles too.
            $user->newQuery()
                ->select($user->getQualifiedKeyName())
                ->where(fn (Builder $users): Builder => $users
                    ->whereHas('roles', fn (Builder $roles): Builder => $roles->withoutGlobalScopes()->where($roleUserTeam, $this->getKey()))
                    ->orWhereHas('permissions', fn (Builder $permissions): Builder => $permissions->where($permissionUserTeam, $this->getKey())))
                ->each(fn (Model&LaratrustUser $user) => $user->flushLaratrustCache());
        }
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
     * The users with direct permissions within the team, through the permission_user table.
     *
     * @return MorphToMany<covariant Model, $this>
     */
    protected function getMorphByUserPermissionRelation(string $relationship): MorphToMany
    {
        return $this->morphedByMany(
            ModelClasses::user($relationship),
            'user',
            Config::get('laratrust.tables.permission_user'),
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
