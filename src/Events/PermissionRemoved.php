<?php

declare(strict_types=1);

namespace Laratrust\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * A permission was removed from a user or a role.
 */
final readonly class PermissionRemoved
{
    /**
     * @param Model           $model      The user or the role.
     * @param int|string      $permission The removed permission key.
     * @param int|string|null $team       The team key, or null when teams are disabled or no team was given.
     */
    public function __construct(
        public Model $model,
        public int|string $permission,
        public int|string|null $team = null,
    ) {
    }
}
