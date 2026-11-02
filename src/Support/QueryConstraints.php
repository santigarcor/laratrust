<?php

declare(strict_types=1);

namespace Laratrust\Support;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Config;
use Laratrust\Models\Team;

/**
 * Constraints shared by the query scopes.
 *
 * They live here instead of in the scopes trait, so they aren't added to
 * the user models, where they could collide with the application's methods.
 *
 * @internal
 */
final class QueryConstraints
{
    /**
     * Match the names like the checks do: "admin|editor" is either of them. Wildcards aren't supported.
     *
     * @template TBuilder of Builder<*>
     *
     * @param TBuilder                                   $query
     * @param string|array<string|BackedEnum>|BackedEnum $name
     *
     * @return TBuilder
     */
    public static function whereNameIs(Builder $query, string|array|BackedEnum $name): Builder
    {
        return $query->whereIn($query->qualifyColumn('name'), app(PermissionRegistry::class)->names($name));
    }

    /**
     * Only keep the assignments made in the given team, following the same rules as the checks:
     * no constraint when teams are disabled, or when no team is given and the check isn't strict,
     * and no match when the team name doesn't exist.
     *
     * @template TBuilder of Builder<*>
     *
     * @param TBuilder $query
     * @param  BelongsToMany<*, *, *, *>  $relation  The relation whose pivot holds the team.
     *
     * @return TBuilder
     */
    public static function constrainToTeam(Builder $query, BelongsToMany $relation, mixed $team): Builder
    {
        if (!Config::get('laratrust.teams.enabled') || ($team === null && !Config::get('laratrust.teams.strict_check'))) {
            return $query;
        }

        $teamId = app(PermissionRegistry::class)->findIdFor($team, 'team');
        $column = $relation->qualifyPivotColumn(Team::modelForeignKey());

        return $teamId === false ? $query->whereIn($column, []) : $query->where($column, $teamId);
    }
}
