<?php

declare(strict_types=1);

namespace Laratrust\Panel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\Role;

/**
 * The restrictions set in "laratrust.panel.roles_restrictions" for the admin panel.
 *
 * @internal
 */
final class RoleRestrictions
{
    /**
     * Can the role and its permissions be edited?
     */
    public static function isEditable(string|(Model&Role) $role): bool
    {
        return ! self::isRestricted('not_editable', $role);
    }

    /**
     * Can the role be deleted?
     */
    public static function isDeletable(string|(Model&Role) $role): bool
    {
        return ! self::isRestricted('not_deletable', $role);
    }

    /**
     * Can the role be removed from the users that have it?
     */
    public static function isRemovable(string|(Model&Role) $role): bool
    {
        return ! self::isRestricted('not_removable', $role);
    }

    private static function isRestricted(string $restriction, string|(Model&Role) $role): bool
    {
        $name = is_string($role) ? $role : $role->getAttribute('name');

        return in_array($name, Config::get("laratrust.panel.roles_restrictions.{$restriction}") ?? [], true);
    }
}
