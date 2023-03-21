<?php

namespace Laratrust;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\Config;

class Helper
{
    /**
     * Get the id of the given object, string, int, uuid, array.
     */
    public static function getIdFor(mixed $object, string $type): int|string|null
    {
        if (
            is_null($object)
            || ($type === 'team' && !Config::get('laratrust.teams.enabled'))
        ) {
            return null;
        }

        if ($object instanceof UuidInterface) {
            return (string)$object;
        }

        if (is_object($object)) {
            return $object->getKey();
        }

        if (is_array($object)) {
            return $object['id'];
        }

        if (is_numeric($object)) {
            return $object;
        }

        if (is_string($object)) {
            return call_user_func_array([
                Config::get("laratrust.models.{$type}"), 'where'
            ], ['name', $object])->firstOrFail()->getKey();
        }

        throw new InvalidArgumentException(
            'getIdFor function only supports UuidInterface, Model, array{id: string}, int, string'
        );
    }

    /**
     * Checks if the string passed contains a pipe '|' and explodes the string to an array.
     */
    public static function standardize(
        string|array $value,
        bool $toArray = false
    ): string|array {
        if (is_array($value) || ((strpos($value, '|') === false) && !$toArray)) {
            return $value;
        }

        return explode('|', $value);
    }

    /**
     * Return two arrays with the filtered permissions between the permissions
     * with wildcard and the permissions without it.
     *
     * @param array $permissions
     * @return array [$wildcard, $noWildcard]
     */
    public static function getPermissionWithAndWithoutWildcards($permissions)
    {
        $wildcard = [];
        $noWildcard = [];

        foreach ($permissions as $permission) {
            if (strpos($permission, '*') === false) {
                $noWildcard[] = $permission;
            } else {
                $wildcard[] = str_replace('*', '%', $permission);
            }
        }

        return [$wildcard, $noWildcard];
    }

    /**
     * Check if a role is editable in the admin panel.
     *
     * @param string|\Laratrust\Models\LaratrustRole $role
     * @return bool
     */
    public static function roleIsEditable($role)
    {
        $roleName = is_string($role) ? $role : $role->name;

        return ! in_array(
            $roleName,
            Config::get('laratrust.panel.roles_restrictions.not_editable') ?? []
        );
    }

    /**
     * Check if a role is deletable in the admin panel.
     *
     * @param string|\Laratrust\Models\LaratrustRole $role
     * @return bool
     */
    public static function roleIsDeletable($role)
    {
        $roleName = is_string($role) ? $role : $role->name;

        return ! in_array(
            $roleName,
            Config::get('laratrust.panel.roles_restrictions.not_deletable') ?? []
        );
    }

    /**
     * Check if a role is removable in the admin panel.
     *
     * @param string|\Laratrust\Models\LaratrustRole $role
     * @return bool
     */
    public static function roleIsRemovable($role)
    {
        $roleName = is_string($role) ? $role : $role->name;

        return ! in_array(
            $roleName,
            Config::get('laratrust.panel.roles_restrictions.not_removable') ?? []
        );
    }
}
