<?php

declare(strict_types=1);

namespace Laratrust\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * A user's roles were synced.
 */
final readonly class RoleSynced
{
    /**
     * @param  Model  $model  The user.
     * @param  array{attached: array<int, int|string>, detached: array<int, int|string>, updated: array<int, int|string>}  $changes  The changes made by the sync.
     * @param  int|string|null  $team  The team key, or null when teams are disabled or no team was given.
     */
    public function __construct(
        public Model $model,
        public array $changes,
        public int|string|null $team = null,
    ) {}
}
