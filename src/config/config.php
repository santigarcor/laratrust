<?php

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Laratrust Role Model
    |--------------------------------------------------------------------------
    |
    | This is the Role model used by Laratrust to create correct relations.  Update
    | the role if it is in a different namespace.
    |
    */
    'role' => 'App\Role',

    /*
    |--------------------------------------------------------------------------
    | Laratrust Roles Table
    |--------------------------------------------------------------------------
    |
    | This is the roles table used by Laratrust to save roles to the database.
    |
    */
    'roles_table' => 'roles',

    /*
    |--------------------------------------------------------------------------
    | Laratrust Permission Model
    |--------------------------------------------------------------------------
    |
    | This is the Permission model used by Laratrust to create correct relations.
    | Update the permission if it is in a different namespace.
    |
    */
    'permission' => 'App\Permission',

    /*
    |--------------------------------------------------------------------------
    | Laratrust Permissions Table
    |--------------------------------------------------------------------------
    |
    | This is the permissions table used by Laratrust to save permissions to the
    | database.
    |
    */
    'permissions_table' => 'permissions',

    /*
    |--------------------------------------------------------------------------
    | Laratrust Groups Model
    |--------------------------------------------------------------------------
    |
    | This is the Group model used by Laratrust to create correct relations.
    | Update the group if it is in a different namespace.
    |
    */
    'group' => 'App\Group',

    /*
    |--------------------------------------------------------------------------
    | Laratrust Groups Table
    |--------------------------------------------------------------------------
    |
    | This is the groups table used by Laratrust to save group to the
    | database.
    |
    */
    'groups_table' => 'groups',

    /*
    |--------------------------------------------------------------------------
    | Laratrust permission_role Table
    |--------------------------------------------------------------------------
    |
    | This is the permission_role table used by Laratrust to save relationship
    | between permissions and roles to the database.
    |
    */
    'permission_role_table' => 'permission_role',

    /*
    |--------------------------------------------------------------------------
    | Laratrust role_user Table
    |--------------------------------------------------------------------------
    |
    | This is the role_user table used by Laratrust to save assigned roles to the
    | database.
    |
    */
    'role_user_table' => 'role_user',

    /*
    |--------------------------------------------------------------------------
    | Laratrust permission_user Table
    |--------------------------------------------------------------------------
    |
    | This is the permission_user table used by Laratrust to save relationship
    | between permissions and users to the database.
    |
    */
    'permission_user_table' => 'permission_user',

    /*
    |--------------------------------------------------------------------------
    | User Foreign key on Laratrust's role_user Table (Pivot)
    |--------------------------------------------------------------------------
    */
    'user_foreign_key' => 'user_id',

    /*
    |--------------------------------------------------------------------------
    | Role Foreign key on Laratrust's role_user and permission_role Tables (Pivot)
    |--------------------------------------------------------------------------
    */
    'role_foreign_key' => 'role_id',

    /*
    |--------------------------------------------------------------------------
    | Group Foreign key on Laratrust's role_user table (Pivot)
    |--------------------------------------------------------------------------
    */
    'group_foreign_key' => 'group_id',

    /*
    |--------------------------------------------------------------------------
    | Permission Foreign key on Laratrust's permission_role Table (Pivot)
    |--------------------------------------------------------------------------
    */
    'permission_foreign_key' => 'permission_id',
    
    /*
    |--------------------------------------------------------------------------
    | Method to be called in the middleware return case
    | Available: abort|redirect
    |--------------------------------------------------------------------------
    */
    'middleware_handling' => 'abort',

    /*
    |--------------------------------------------------------------------------
    | Parameter passed to the middleware_handling method
    |--------------------------------------------------------------------------
    */
    'middleware_params' => '403',
];
