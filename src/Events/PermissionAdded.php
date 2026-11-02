<?php

declare(strict_types=1);

namespace Laratrust\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * A permission was given to a user or a role.
 */
final readonly class PermissionAdded
{
    /**
     * @param Model           $model      The user or the role.
     * @param int|string      $permission The added permission key.
     * @param int|string|null $team       The team key, or null when teams are disabled or no team was given.
     */
    public function __construct(
        public Model $model,
        public int|string $permission,
        public int|string|null $team = null,
    ) {
    }
}
