---
sidebarDepth: 2
---

# API

## User
* ### `public cachedRoles`
  - **Returns:** `Illuminate\Database\Eloquent\Collection|array`
  - **Usage:**

    Get the user cached roles. If the `laratrust.use_cache` is set to `true` it will return an array and if it set to `false` it will return a collection.

- ### `public cachedPermissions`
  - **Returns:** `Illuminate\Database\Eloquent\Collection|array`
  - **Usage:**

    Get the user cached permissions. If `laratrust.use_cache` is set to `true` it will return an array and if it set to `false` it will return a collection.

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
    - `$team = null`
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
    - `$team = null`
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
    - `$team = null`
    - `$options = array`
  - **Returns:** `bool|array`
  - **Usage:**

    Check if an user has some role(s) or/and permission(s).
    ```php
    $user->ability('admin', 'edit-user');
    $user->hasPermission(['admin', 'regular'], ['edit-user', 'create-user']);
    $user->hasPermission(
          ['admin', 'regular']
        , ['edit-user', 'create-user']
        , ['validate_all' => true]
    ); // Will require all
    ```

    And if teams are being used:
    ```php
    $user->ability('admin', 'edit-user', 'human-resources');
    $user->hasPermission(['admin', 'regular'], ['edit-user', 'create-user'], 'human-resources');
    $user->hasPermission(
          ['admin', 'regular']
        , ['edit-user', 'create-user']
        , 'human-resources'
        , ['validate_all' => true]
    ); // Will require all
    ```

## Role

## Permission

## Team
