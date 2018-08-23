---
sidebarDepth: 2
---

# API

## User

- ### `public roles`
  - **Returns:** `Illuminate\Database\Eloquent\Relations\MorphToMany`
  - **Usage:**

    Get the morph to many user relationship with the roles.

- ### `public rolesTeams`
  - **Returns:** `Illuminate\Database\Eloquent\Relations\MorphToMany|null`
  - **Usage:**

    Get the morph to many user relationship with the teams through the roles. If `laratrust.use_teams` is set to `false` it will return null.

- ### `public permissions`
  - **Returns:** `Illuminate\Database\Eloquent\Relations\MorphToMany`
  - **Usage:**

    Get the morph to many user relationship with the permissions.

- ### `public hasRole`
  - **Arguments:**
    - `$role (string, int, Illuminate\Database\Eloquent\Model)`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
    - `$requireAll = false`
  - **Returns:** `bool`
  - **Usage:**

    Check if an user has some role(s).
    ```php
    $user->hasRole('admin');
    $user->hasRole(['admin', 'regular']);
    $user->hasRole(['admin', 'regular'], true); // Will require all
    ```

    And if teams are being used:
    ```php
    $user->hasRole('admin', 'human-resources');
    $user->hasRole(['admin', 'regular'], 'human-resources');
    $user->hasRole(['admin', 'regular'], 'human-resources', true); // Will require all
    ```

- ### `public isA`
  Is the same as [hasRole](#public-hasrole).

- ### `public isAn`
  Is the same as [hasRole](#public-hasrole).

- ### `public hasPermission`
  - **Arguments:**
    - `$permission (string, int, Illuminate\Database\Eloquent\Model)`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
    - `$requireAll = false`
  - **Returns:** `bool`
  - **Usage:**

    Check if an user has some permission(s).
    ```php
    $user->hasPermission('edit-user');
    $user->hasPermission(['edit-user', 'create-user']);
    $user->hasPermission(['edit-user', 'create-user'], true); // Will require all
    ```

    And if teams are being used:
    ```php
    $user->hasPermission('edit-user', 'human-resources');
    $user->hasPermission(['edit-user', 'create-user'], 'human-resources');
    $user->hasPermission(['edit-user', 'create-user'], 'human-resources', true); // Will require all
    ```

- ### `public can`
  Is the same as [hasPermission](#public-haspermission).

- ### `public isAbleTo`
  Is the same as [hasPermission](#public-haspermission).

- ### `public ability`
  - **Arguments:**
    - `$roles (string, array)`
    - `$permissions (string, array)`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
    - `$options = array`
  - **Returns:** `bool|array`
  - **Usage:**

    Check if an user has some role(s) or/and permission(s).
    ```php
    $user->ability('admin', 'edit-user');
    $user->ability(['admin', 'regular'], ['edit-user', 'create-user']);
    $user->ability(
          ['admin', 'regular']
        , ['edit-user', 'create-user']
        , ['validate_all' => true]
    ); // Will require all
    ```

    And if teams are being used:
    ```php
    $user->ability('admin', 'edit-user', 'human-resources');
    $user->ability(['admin', 'regular'], ['edit-user', 'create-user'], 'human-resources');
    $user->ability(
          ['admin', 'regular']
        , ['edit-user', 'create-user']
        , 'human-resources'
        , ['validate_all' => true]
    ); // Will require all
    ```

- ### `public attachRole`
  - **Arguments:**
    - `$role (string, int, Illuminate\Database\Eloquent\Model)`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Attach a role to an user.
    ```php
    $user->attachRole('admin');
    ```

    And if teams are being used:
    ```php
    $user->attachRole('admin', 'human-resources');
    ```

- ### `public detachRole`
  - **Arguments:**
    - `$role (string, int, Illuminate\Database\Eloquent\Model)`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Detach a role from an user.
    ```php
    $user->detachRole('admin');
    ```

    And if teams are being used:
    ```php
    $user->detachRole('admin', 'human-resources');
    ```

- ### `public attachRoles`
  - **Arguments:**
    - `$roles array`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Attach multiple roles to an user.
    ```php
    $user->attachRoles(['admin', 'regular']);
    ```

    And if teams are being used:
    ```php
    $user->attachRoles(['admin', 'regular'], 'human-resources');
    ```

- ### `public detachRoles`
  - **Arguments:**
    - `$roles array`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Detach multiple roles from an user.
    ```php
    $user->detachRoles(['admin', 'regular']);
    ```

    And if teams are being used:
    ```php
    $user->detachRoles(['admin', 'regular'], 'human-resources');
    ```

- ### `public syncRoles`
  - **Arguments:**
    - `$roles array`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
    - `$detaching = true`
  - **Returns:** `$this`
  - **Usage:**

    Sync roles to the user.
    ```php
    $user->syncRoles(['admin', 'regular']);
    $user->syncRoles(['admin', 'regular'], null, false);
    ```

    And if teams are being used:
    ```php
    $user->syncRoles(['admin', 'regular'], 'human-resources');
    $user->syncRoles(['admin', 'regular'], 'human-resources', false);
    ```
- ### `public syncRolesWithoutDetaching`
  Is the same as [synRoles](#public-syncroles) but with `$detaching = false`.

- ### `public attachPermission`
  - **Arguments:**
    - `$permission (string, int, Illuminate\Database\Eloquent\Model)`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Attach a permission to an user.
    ```php
    $user->attachPermission('edit-user');
    ```

    And if teams are being used:
    ```php
    $user->attachPermission('edit-user', 'human-resources');
    ```

- ### `public detachPermission`
  - **Arguments:**
    - `$permission (string, int, Illuminate\Database\Eloquent\Model)`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Detach a permission from an user.
    ```php
    $user->detachPermission('edit-user');
    ```

    And if teams are being used:
    ```php
    $user->detachPermission('edit-user', 'human-resources');
    ```

- ### `public attachPermissions`
  - **Arguments:**
    - `$permissions array`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Attach multiple permissions to an user.
    ```php
    $user->attachPermissions(['edit-user', 'create-user']);
    ```

    And if teams are being used:
    ```php
    $user->attachPermissions(['edit-user', 'create-user'], 'human-resources');
    ```

- ### `public detachPermissions`
  - **Arguments:**
    - `$permissions array`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Detach multiple permissions from an user.
    ```php
    $user->detachPermissions(['edit-user', 'create-user']);
    ```

    And if teams are being used:
    ```php
    $user->detachPermissions(['edit-user', 'create-user'], 'human-resources');
    ```

- ### `public syncPermissions`
  - **Arguments:**
    - `$permissions array`
    - `$team = null (string, int, Illuminate\Database\Eloquent\Model)`
    - `$detaching = true`
  - **Returns:** `$this`
  - **Usage:**

    Sync permissions to the user.
    ```php
    $user->syncPermissions(['edit-user', 'create-user']);
    $user->syncPermissions(['edit-user', 'create-user'], null, false);
    ```

    And if teams are being used:
    ```php
    $user->syncPermissions(['edit-user', 'create-user'], 'human-resources');
    $user->syncPermissions(['edit-user', 'create-user'], 'human-resources', false);
    ```

- ### `public syncPermissionsWithoutDetaching`
  Is the same as [synRoles](#public-syncpermissions) but with `$detaching = false`.

- ### `public owns`
  - **Arguments:**
    - `$thing Object`
    - `$foreignKeyName = null (string)`
  - **Returns:** `bool`
  - **Usage:**

    Check if an user owns an object.
    ```php
    $car = Car::find(1);

    $user->owns($car);
    $user->owns($car, 'owner_key_id'); // Will compare $user->id == $car->owner_key_id
    ```

- ### `public hasRoleAndOwns`
  - **Arguments:**
    - `$role (string, int, Illuminate\Database\Eloquent\Model)`
    - `$thing Object`
    - `$options = []`
  - **Returns:** `bool`
  - **Usage:**

    Check if an user owns an object and has a role.

    ```php
    $car = Car::find(1);

    $user->hasRoleAndOwns('admin' ,$car, [
      'requireAll' => false,
      'team' => 'some_team', // can be null
      'foreignKeyName' => 'owner_key_id', // can be null
    ]);
    ```

- ### `public canAndOwns`
  - **Arguments:**
    - `$permission (string, int, Illuminate\Database\Eloquent\Model)`
    - `$thing Object`
    - `$options = []`
  - **Returns:** `bool`
  - **Usage:**

    Check if an user owns an object and has a permission.

    ```php
    $car = Car::find(1);

    $user->canAndOwns('edit-car' ,$car, [
      'requireAll' => false,
      'team' => 'some_team', // can be null
      'foreignKeyName' => 'owner_key_id', // can be null
    ]);
    ```

- ### `public allPermissions`
  - **Returns:** `Illuminate\Database\Eloquent\Collection`
  - **Usage:**

    Get all the user permissions.

    ```php
    $user->allPermissions();
    ```

- ### `public flushCache`
  - **Usage:**

    Flush the cache related with the user roles and permissions.

    ```php
    $user->flushCache();
    ```

- ### `public static laratrustObserve`
  - **Arguments:**
    - `$class (string, Object)`
  - **Returns:** `void`
  - **Usage:**

    Register an observer to the Laratrust events.

    ```php
    User::laratrustObserve(UserObserver::class);
    User::laratrustObserve(new UserObserver);
    ```

- ### `public static roleAttached`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a role attached laratrust event with the dispatcher.

    ```php
    User::roleAttached(function (...) {});
    ```

- ### `public static roleDetached`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a role detached laratrust event with the dispatcher.

    ```php
    User::roleDetached(function (...) {});
    ```

- ### `public static permissionAttached`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a permission attached laratrust event with the dispatcher.

    ```php
    User::permissionAttached(function (...) {});
    ```

- ### `public static permissionDetached`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a permission detached laratrust event with the dispatcher.

    ```php
    User::permissionDetached(function (...) {});
    ```

- ### `public static roleSynced`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a role synced laratrust event with the dispatcher.

    ```php
    User::roleSynced(function (...) {});
    ```

- ### `public static permissionSynced`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a permission synced laratrust event with the dispatcher.

    ```php
    User::permissionSynced(function (...) {});
    ```

## Role
- ### `public permissions`
  - **Returns:** `Illuminate\Database\Eloquent\Relations\MorphToMany`
  - **Usage:**

    Get the morph to many user relationship with the permissions.

- ### `public attachPermission`
  - **Arguments:**
    - `$permission (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Attach a permission to a role.
    ```php
    $role->attachPermission('edit-user');
    ```

- ### `public detachPermission`
  - **Arguments:**
    - `$permission (string, int, Illuminate\Database\Eloquent\Model)`
  - **Returns:** `$this`
  - **Usage:**

    Detach a permission from a role.
    ```php
    $role->detachPermission('edit-user');
    ```

- ### `public attachPermissions`
  - **Arguments:**
    - `$permissions array`
  - **Returns:** `$this`
  - **Usage:**

    Attach multiple permissions to a role.
    ```php
    $role->attachPermissions(['edit-user', 'create-user']);
    ```

- ### `public detachPermissions`
  - **Arguments:**
    - `$permissions array`
  - **Returns:** `$this`
  - **Usage:**

    Detach multiple permissions from a role.
    ```php
    $role->detachPermissions(['edit-user', 'create-user']);
    ```

- ### `public syncPermissions`
  - **Arguments:**
    - `$permissions array`
  - **Returns:** `$this`
  - **Usage:**

    Sync permissions to the role.
    ```php
    $role->syncPermissions(['edit-user', 'create-user']);
    ```

- ### `public static laratrustObserve`
  - **Arguments:**
    - `$class (string, Object)`
  - **Returns:** `void`
  - **Usage:**

    Register an observer to the Laratrust events.

    ```php
    Role::laratrustObserve(RoleObserver::class);
    Role::laratrustObserve(new RoleObserver);
    ```


- ### `public static permissionAttached`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a permission attached laratrust event with the dispatcher.

    ```php
    Role::permissionAttached(function (...) {});
    ```

- ### `public static permissionDetached`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a permission detached laratrust event with the dispatcher.

    ```php
    Role::permissionDetached(function (...) {});
    ```

- ### `public static permissionSynced`
  - **Arguments:**
    - `$callback (Closure, string)`
  - **Returns:** `void`
  - **Usage:**

    Register a permission synced laratrust event with the dispatcher.

    ```php
    Role::permissionSynced(function (...) {});
    ```
## Permission
- ### `public roles`
  - **Returns:** `Illuminate\Database\Eloquent\Relations\MorphToMany`
  - **Usage:**

    Get the morph to many permission relationship with the roles.