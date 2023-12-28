<?php

declare(strict_types=1);

namespace Laratrust\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\CheckersManager;
use Laratrust\Checkers\Group\GroupChecker;
use Laratrust\Contracts\Group as GroupContract;
use Laratrust\Helper;
use Laratrust\Traits\DynamicUserRelationshipCalls;
use Laratrust\Traits\HasLaratrustEvents;
use Ramsey\Uuid\UuidInterface;

class Group extends Model implements GroupContract
{
  use HasLaratrustEvents;
  use DynamicUserRelationshipCalls;

  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table;

  protected $fillable = [
    'name',
    'display_name',
    'description',
  ];

  /**
   * Creates a new instance of the model.
   */
  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    $this->table = Config::get('laratrust.tables.groups');
  }

  protected static function booted(): void
  {
    $flushCache = function (Group $group) {
      $group->flushCache();
    };

    // If the group doesn't use SoftDeletes.
    if (method_exists(static::class, 'restored')) {
      static::restored($flushCache);
    }

    static::deleted($flushCache);
    static::saved($flushCache);

    static::deleting(function ($group) {
      if (method_exists($group, 'bootSoftDeletes') && !$group->forceDeleting) {
        return;
      }

      $group->roles()->sync([]);
      $group->permissions()->sync([]);

      foreach (array_keys(Config::get('laratrust.user_models')) as $key) {
        $group->$key()->sync([]);
      }
    });
  }

  /**
   * Return the right checker for the group model.
   */
  protected function laratrustGroupChecker(): GroupChecker
  {
    return (new CheckersManager($this))->getGroupChecker();
  }

  public function getMorphByUserRelation(string $relationship): MorphToMany
  {
    return $this->morphedByMany(
      Config::get('laratrust.user_models')[$relationship],
      'user',
      Config::get('laratrust.tables.group_user'),
      Config::get('laratrust.foreign_keys.group'),
      Config::get('laratrust.foreign_keys.user')
    );
  }

  public function permissions(): BelongsToMany
  {
    return $this->belongsToMany(
      Config::get('laratrust.models.permission'),
      Config::get('laratrust.tables.permission_group'),
      Config::get('laratrust.foreign_keys.group'),
      Config::get('laratrust.foreign_keys.permission')
    );
  }

  public function roles(): BelongsToMany
  {
    return $this->belongsToMany(
      Config::get('laratrust.models.role'),
      Config::get('laratrust.tables.group_role'),
      Config::get('laratrust.foreign_keys.group'),
      Config::get('laratrust.foreign_keys.role')
    );
  }

  public function hasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
  {
    return $this->laratrustGroupChecker()
      ->currentGroupHasPermission($permission, $requireAll);
  }

  public function hasRole(string|array|BackedEnum $role, bool $requireAll = false): bool
  {
    return $this->laratrustGroupChecker()
      ->currentGroupHasRole($role, $requireAll);
  }

  public function syncPermissions(iterable $permissions): static
  {
    $mappedPermissions = [];

    foreach ($permissions as $permission) {
      $mappedPermissions[] = Helper::getIdFor($permission, 'permission');
    }

    $changes = $this->permissions()->sync($mappedPermissions);
    $this->flushCache();
    $this->fireLaratrustEvent('permission.synced', [$this, $changes]);

    return $this;
  }

  public function syncRoles(iterable $roles): static
  {
    $mappedRoles = [];

    foreach ($roles as $role) {
      $mappedRoles[] = Helper::getIdFor($role, 'permission');
    }

    $changes = $this->permissions()->sync($mappedRoles);
    $this->flushCache();
    $this->fireLaratrustEvent('role.synced', [$this, $changes]);

    return $this;
  }

  public function givePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static
  {
    $permission = Helper::getIdFor($permission, 'permission');

    $this->permissions()->attach($permission);
    $this->flushCache();
    $this->fireLaratrustEvent('permission.added', [$this, $permission]);

    return $this;
  }

  public function addRole(array|string|int|Model|UuidInterface|BackedEnum $role): static
  {
    $role = Helper::getIdFor($role, 'role');

    $this->roles()->attach($role);
    $this->flushCache();
    $this->fireLaratrustEvent('role.added', [$this, $role]);

    return $this;
  }

  public function removePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static
  {
    $permission = Helper::getIdFor($permission, 'permission');

    $this->permissions()->detach($permission);
    $this->flushCache();
    $this->fireLaratrustEvent('permission.removed', [$this, $permission]);

    return $this;
  }

  public function removeRole(array|string|int|Model|UuidInterface|BackedEnum $role): static
  {
    $role = Helper::getIdFor($role, 'role');

    $this->roles()->detach($role);
    $this->flushCache();
    $this->fireLaratrustEvent('role.removed', [$this, $role]);

    return $this;
  }

  public function givePermissions(iterable $permissions): static
  {
    foreach ($permissions as $permission) {
      $this->givePermission($permission);
    }

    return $this;
  }

  public function addRoles(iterable $roles): static
  {
    foreach ($roles as $role) {
      $this->addRole($role);
    }

    return $this;
  }

  public function removePermissions(iterable $permissions = null): static
  {
    if (!$permissions) {
      $this->syncPermissions([]);

      return $this;
    }

    foreach ($permissions as $permission) {
      $this->removePermission($permission);
    }

    return $this;
  }

  public function removeRoles(iterable $roles = null): static
  {
    if (!$roles) {
      $this->syncRoles([]);

      return $this;
    }

    foreach ($roles as $role) {
      $this->removeRole($role);
    }

    return $this;
  }

  /**
   * Flush the groups's cache.
   */
  public function flushCache(): void
  {
    $this->laratrustGroupChecker()->currentGroupFlushCache();
  }
}
