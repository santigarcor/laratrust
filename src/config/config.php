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
    | Use MorphMap in relationships between models
    |--------------------------------------------------------------------------
    |
    | If true, the morphMap feature is going to be used. The array values that
    | are going to be used are the ones inside the 'user_models' array.
    |
    */
    'use_morph_map' => false,

    /*
    |--------------------------------------------------------------------------
    | Laratrust User Models
    |--------------------------------------------------------------------------
    |
    | This is the array that contains the information of the user models.
    | This information is used in the add-trait command, and for the roles and
    | permissions relationships with the possible user models.
    |
    | The key in the array is the name of the relationship inside the roles and permissions.
    |
    */
    'user_models' => [
        'users' => 'App\User',
    ],

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
    | Permission Foreign key on Laratrust's permission_role Table (Pivot)
    |--------------------------------------------------------------------------
    */
    'permission_foreign_key' => 'permission_id',

    /*
    |--------------------------------------------------------------------------
    | Use teams feature in the package
    |--------------------------------------------------------------------------
    |
    | This defines is Laratrust is using the teams feature
    |
    */
    'use_teams' => false,

    /*
    |--------------------------------------------------------------------------
    | Laratrust Teams Model
    |--------------------------------------------------------------------------
    |
    | This is the Team model used by Laratrust to create correct relations.
    | Update the team if it is in a different namespace.
    |
    */
    'team' => 'App\Team',

    /*
    |--------------------------------------------------------------------------
    | Laratrust Teams Table
    |--------------------------------------------------------------------------
    |
    | This is the teams table used by Laratrust to save team to the
    | database.
    |
    */
    'teams_table' => 'teams',

    /*
    |--------------------------------------------------------------------------
    | Team Foreign key on Laratrust's role_user table (Pivot)
    |--------------------------------------------------------------------------
    */
    'team_foreign_key' => 'team_id',
    
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

    /*
    |--------------------------------------------------------------------------
    | Supported cases for the magic can method
    | Available: camel_case|snake_case|kebab_case
    |--------------------------------------------------------------------------
    */
    'magic_can_method_case' => 'kebab_case'
];
