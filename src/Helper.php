<?php

namespace Laratrust;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\Config;

class Helper
{
    /**
     * Gets the id from an array, object or integer.
     *
     * @param  mixed  $object
     * @param  string  $type
     * @return int
     */
    public static function getIdFor($object, string $type)
    {
        if (is_null($object)) {
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
            'getIdFor function only accepts an integer, a Model object or an array with an "id" key'
        );
    }

    /**
     * Assing the real values to the team and requireAllOrOptions parameters.
     *
     * @param  mixed  $team
     * @param  mixed  $requireAllOrOptions
     * @return array
     */
    public static function assignRealValuesTo($team, $requireAllOrOptions, $method)
    {
        return [
            ($method($team) ? null : $team),
            ($method($team) ? $team : $requireAllOrOptions),
        ];
    }

    /**
     * Checks if the string passed contains a pipe '|' and explodes the string to an array.
     */
    public static function standardize(
        string|array $value,
        bool $toArray = false
    ): string|array
    {
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
