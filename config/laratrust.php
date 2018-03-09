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
    | Use cache in the package
    |--------------------------------------------------------------------------
    |
    | Defines if Laratrust will use Laravel's Cache to cache the roles and permissions.
    |
    */
    'use_cache' => true,

    /*
    |--------------------------------------------------------------------------
    | Use teams feature in the package
    |--------------------------------------------------------------------------
    |
    | Defines if Laratrust will use the teams feature.
    | Please check the docs to see what you need to do in case you have the package already configured.
    |
    */
    'use_teams' => false,

    /*
    |--------------------------------------------------------------------------
    | Strict check for roles/permissions inside teams
    |--------------------------------------------------------------------------
    |
    | Determines if a strict check should be done when checking if a role or permission
    | is attached inside a team.
    | If it's false, when checking a role/permission without specifying the team,
    | it will check only if the user has attached that role/permission ignoring the team.
    |
    */
    'teams_strict_check' => false,

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
    | Laratrust Models
    |--------------------------------------------------------------------------
    |
    | These are the models used by Laratrust to define the roles, permissions and teams.
    | If you want the Laratrust models to be in a different namespace or
    | to have a different name, you can do it here.
    |
    */
    'models' => [
        /**
         * Role model
         */
        'role' => 'App\Role',

        /**
         * Permission model
         */
        'permission' => 'App\Permission',

        /**
         * Team model
         */
        'team' => 'App\Team',

    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Tables
    |--------------------------------------------------------------------------
    |
    | These are the tables used by Laratrust to store all the authorization data.
    |
    */
    'tables' => [
        /**
         * Roles table.
         */
        'roles' => 'roles',

        /**
         * Permissions table.
         */
        'permissions' => 'permissions',

        /**
         * Teams table.
         */
        'teams' => 'teams',

        /**
         * Role - User intermediate table.
         */
        'role_user' => 'role_user',

        /**
         * Permission - User intermediate table.
         */
        'permission_user' => 'permission_user',

        /**
         * Permission - Role intermediate table.
         */
        'permission_role' => 'permission_role',

    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Foreign Keys
    |--------------------------------------------------------------------------
    |
    | These are the foreign keys used by laratrust in the intermediate tables.
    |
    */
    'foreign_keys' => [
        /**
         * User foreign key on Laratrust's role_user and permission_user tables.
         */
        'user' => 'user_id',

        /**
         * Role foreign key on Laratrust's role_user and permission_role tables.
         */
        'role' => 'role_id',

        /**
         * Role foreign key on Laratrust's permission_user and permission_role tables.
         */
        'permission' => 'permission_id',

        /**
         * Role foreign key on Laratrust's role_user and permission_user tables.
         */
        'team' => 'team_id',

    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Middleware
    |--------------------------------------------------------------------------
    |
    | This configuration helps to customize the Laratrust middleware behavior.
    |
    */
    'middleware' => [
        /**
         * Define if the laratrust middleware are registered automatically in the service provider
         */
        'register' => true,

        /**
         * Method to be called in the middleware return case.
         * Available: abort|redirect
         */
        'handling' => 'abort',

        /**
         * Parameter passed to the middleware_handling method
         */
        'params' => '403',

    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Magic 'can' Method
    |--------------------------------------------------------------------------
    |
    | Supported cases for the magic can method (Refer to the docs).
    | Available: camel_case|snake_case|kebab_case
    |
    */
    'magic_can_method_case' => 'kebab_case',
];
