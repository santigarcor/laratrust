<?php

declare(strict_types=1);

namespace Laratrust\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * A role was added to a user.
 */
final readonly class RoleAdded
{
    /**
     * @param  Model  $model  The user.
     * @param  int|string  $role  The added role key.
     * @param  int|string|null  $team  The team key, or null when teams are disabled or no team was given.
     */
    public function __construct(
        public Model $model,
        public int|string $role,
        public int|string|null $team = null,
    ) {}
}
