<?php

declare(strict_types=1);

namespace Laratrust\Checkers;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Helper;
use Laratrust\Support\PermissionLookup;
use Laratrust\Support\PermissionRegistry;

/**
 * @internal
 */
final readonly class UserChecker
{
    private PermissionRegistry $registry;

    public function __construct(private Model&LaratrustUser $user)
    {
        $this->registry = app(PermissionRegistry::class);
    }

    /**
     * Get the names of the user roles.
     *
     * @return list<string>
     */
    public function roles(mixed $team = null): array
    {
        return $this->registry->lookupFor($this->user)->roleNames($this->teamScope($team));
    }

    /**
     * Check if the user has a role by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $name
     */
    public function hasRole(string|array|BackedEnum $name, mixed $team = null, bool $requireAll = false): bool
    {
        ['team' => $team, 'require_all' => $requireAll] = $this->getRealValues($team, $requireAll, is_bool(...));

        $lookup = $this->registry->lookupFor($this->user);
        $teamScope = $this->teamScope($team);

        return $this->checkAnyOrAll(
            Helper::standardize($name, true),
            $requireAll,
            fn (string $role): bool => $lookup->hasRole($role, $teamScope),
        );
    }

    /**
     * Check if the user has a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function hasPermission(string|array|BackedEnum $permission, mixed $team = null, bool $requireAll = false): bool
    {
        ['team' => $team, 'require_all' => $requireAll] = $this->getRealValues($team, $requireAll, is_bool(...));

        $lookup = $this->registry->lookupFor($this->user);
        $teamScope = $this->teamScope($team);

        return $this->checkAnyOrAll(
            Helper::standardize($permission, true),
            $requireAll,
            fn (string $name): bool => $lookup->hasPermission($name, $teamScope),
        );
    }

    /**
     * Check role(s) and permission(s).
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $roles
     * @param  string|array<string|BackedEnum>|BackedEnum  $permissions
     * @param  array{validate_all?: bool, return_type?: 'boolean'|'array'|'both'}  $options
     * @return bool|array{roles: array<string, bool>, permissions: array<string, bool>}|array{bool, array{roles: array<string, bool>, permissions: array<string, bool>}}
     *
     * @throws InvalidArgumentException
     */
    public function ability(
        string|array|BackedEnum $roles,
        string|array|BackedEnum $permissions,
        mixed $team = null,
        array $options = []
    ): array|bool {
        ['team' => $team, 'options' => $options] = $this->getRealValues($team, $options, is_array(...));
        $roles = Helper::standardize($roles, true);
        $permissions = Helper::standardize($permissions, true);

        $options = $this->validateAndSetDefaults($options);

        if ($options['return_type'] == 'boolean') {
            $hasRoles = $this->hasRole($roles, $team, $options['validate_all']);
            $hasPermissions = $this->hasPermission($permissions, $team, $options['validate_all']);

            return $options['validate_all']
                ? $hasRoles && $hasPermissions
                : $hasRoles || $hasPermissions;
        }

        $checkedRoles = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role, $team);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->hasPermission($permission, $team);
        }

        // If validate all, there should not be any false; otherwise there must be at least one true.
        $validateAll = $options['validate_all']
            ? ! (in_array(false, $checkedRoles) || in_array(false, $checkedPermissions))
            : in_array(true, $checkedRoles) || in_array(true, $checkedPermissions);

        if ($options['return_type'] == 'array') {
            return ['roles' => $checkedRoles, 'permissions' => $checkedPermissions];
        }

        return [$validateAll, ['roles' => $checkedRoles, 'permissions' => $checkedPermissions]];
    }

    /**
     * Forget the user's roles and permissions from the cache and the current request.
     */
    public function flush(): void
    {
        $this->registry->forgetUser($this->user);
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');
        $this->user->unsetRelation('permissionsTeams');
        $this->user->unsetRelation('rolesTeams');
    }

    /**
     * Resolve which team scope a check should look into: null means any team.
     */
    private function teamScope(mixed $team): ?string
    {
        if (! Config::get('laratrust.teams.enabled')) {
            return null;
        }

        $teamId = $this->registry->idFor($team, 'team');

        if ($teamId === null) {
            return Config::get('laratrust.teams.strict_check') ? PermissionLookup::NO_TEAM : null;
        }

        return PermissionLookup::teamScopeFor($teamId);
    }

    /**
     * Run the check against each name and combine the results.
     *
     * With $requireAll every name must pass, otherwise one passing name is
     * enough. Both stop at the first result that decides the outcome. An empty
     * list of names always passes.
     *
     * @param  list<string>  $names  Role or permission names to check.
     * @param  callable(string): bool  $check  Tells if the user has a single name.
     */
    private function checkAnyOrAll(array $names, bool $requireAll, callable $check): bool
    {
        $names = Collection::make($names);

        return $requireAll ? $names->every($check) : $names->isEmpty() || $names->contains($check);
    }

    /**
     * Assign the real values to the team and requireAllOrOptions parameters.
     *
     * @param  callable(mixed): bool  $methodToEvaluate
     * @return array{team: mixed, options: mixed, require_all: mixed}
     */
    private function getRealValues(mixed $team, mixed $requireAllOrOptions, callable $methodToEvaluate): array
    {
        $requireAllOrOptions = ($methodToEvaluate($team) ? $team : $requireAllOrOptions);

        return [
            'team' => ($methodToEvaluate($team) ? null : $team),
            'options' => $requireAllOrOptions,
            'require_all' => $requireAllOrOptions,
        ];
    }

    /**
     * Validate the ability() options and fill in the missing ones.
     *
     * validate_all defaults to false and return_type to 'boolean'.
     *
     * @param  array<string, mixed>  $options
     * @return array{validate_all: bool, return_type: 'boolean'|'array'|'both'}
     *
     * @throws InvalidArgumentException When an option has a value that isn't allowed.
     */
    private function validateAndSetDefaults(array $options): array
    {
        $validateAll = $options['validate_all'] ?? false;
        $returnType = $options['return_type'] ?? 'boolean';

        if (! is_bool($validateAll) || ! in_array($returnType, ['boolean', 'array', 'both'], true)) {
            throw new InvalidArgumentException();
        }

        return ['validate_all' => $validateAll, 'return_type' => $returnType];
    }
}
