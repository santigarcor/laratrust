<?php

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
    | Checkers
    |--------------------------------------------------------------------------
    |
    | Manage Laratrust's role and permissions checkers configurations.
    |
    */
    'checkers' => [

        /*
        |--------------------------------------------------------------------------
        | Which permissions checker to use.
        |--------------------------------------------------------------------------
        |
        | Defines if you want to use the roles and permissions checker.
        | Available:
        | - default: Check for the roles and permissions using the method that Laratrust
        |            has always used.
        | - query: Check for the roles and permissions using direct queries to the database.
        |           This method doesn't support cache yet.
        | - class that extends Laratrust\Checkers\User\UserChecker
        */
        'user' => 'default',

        /*
        |--------------------------------------------------------------------------
        | Which role checker to use.
        |--------------------------------------------------------------------------
        |
        | Defines if you want to use the roles and permissions checker.
        | Available:
        | - default: Check for the roles and permissions using the method that Laratrust
                     has always used.
        | - query: Check for the roles and permissions using direct queries to the database.
        |          This method doesn't support cache yet.
        | - class that extends Laratrust\Checkers\Role\RoleChecker
        */
        'role' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Manage Laratrust's cache configurations. It uses the driver defined in the
    | config/cache.php file.
    |
    */
    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Use cache in the package
        |--------------------------------------------------------------------------
        |
        | Defines if Laratrust will use Laravel's Cache to cache the roles and permissions.
        | NOTE: Currently the database check does not use cache.
        |
        */
        'enabled' => env('LARATRUST_ENABLE_CACHE', env('APP_ENV') === 'production'),

        /*
        |--------------------------------------------------------------------------
        | Time to store in cache Laratrust's roles and permissions.
        |--------------------------------------------------------------------------
        |
        | Determines the time in SECONDS to store Laratrust's roles and permissions in the cache.
        |
        */
        'expiration_time' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust User Models
    |--------------------------------------------------------------------------
    |
    | This is the array that contains the information of the user models.
    | This information is used in the add-trait command, for the roles and
    | permissions relationships with the possible user models, and the
    | administration panel to add roles and permissions to the users.
    |
    | The key in the array is the name of the relationship inside the roles and permissions.
    |
    */
    'user_models' => [
        'users' => \App\Models\User::class,
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

        'role' => \App\Models\Role::class,

        'permission' => \App\Models\Permission::class,

        /**
         * Will be used only if the teams functionality is enabled.
         */
        'team' => \App\Models\Team::class,
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

        'roles' => 'roles',

        'permissions' => 'permissions',

        /**
         * Will be used only if the teams functionality is enabled.
         */
        'teams' => 'teams',

        'role_user' => 'role_user',

        'permission_user' => 'permission_user',

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
         * Handlers for the unauthorized method in the middlewares.
         * The name of the handler must be the same as the handling.
         */
        'handlers' => [
            /**
             * Aborts the execution with a 403 code and allows you to provide the response text
             */
            'abort' => [
                'code' => 403,
                'message' => 'User does not have any of the necessary access rights.'
            ],

            /**
             * Redirects the user to the given url.
             * If you want to flash a key to the session,
             * you can do it by setting the key and the content of the message
             * If the message content is empty it won't be added to the redirection.
             */
            'redirect' => [
                'url' => '/home',
                'message' => [
                    'key' => 'error',
                    'content' => ''
                ]
            ]
        ]
    ],

    'teams' => [
        /*
        |--------------------------------------------------------------------------
        | Use teams feature in the package
        |--------------------------------------------------------------------------
        |
        | Defines if Laratrust will use the teams feature.
        | Please check the docs to see what you need to do in case you have the package already configured.
        |
        */
        'enabled' => false,

        /*
        |--------------------------------------------------------------------------
        | Strict check for roles/permissions inside teams
        |--------------------------------------------------------------------------
        |
        | Determines if a strict check should be done when checking if a role or permission is added inside a team.
        | If it's false, when checking a role/permission without specifying the team,
        | it will check only if the user has added that role/permission ignoring the team.
        |
        */
        'strict_check' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Permissions as Gates
    |--------------------------------------------------------------------------
    |
    | Determines if you can check if a user has a permission using the "can" method.
    |
    */
    'permissions_as_gates' => false,

    /*
    |--------------------------------------------------------------------------
    | Laratrust Panel
    |--------------------------------------------------------------------------
    |
    | Section to manage everything related with the admin panel for the roles and permissions.
    |
    */
    'panel' => [
        /*
        |--------------------------------------------------------------------------
        | Laratrust Panel Register
        |--------------------------------------------------------------------------
        |
        | This manages if routes used for the admin panel should be registered.
        | Turn this value to false if you don't want to use Laratrust admin panel
        |
        */
        'register' => false,

        /*
        |--------------------------------------------------------------------------
        | Laratrust Panel Domain
        |--------------------------------------------------------------------------
        |
        | This is the Domain Laratrust panel for roles and permissions
        | will be accessible from.
        |
        */
        'domain' => env('LARATRUST_PANEL_DOMAIN', env('LARATRUST_PANEL_DOMAIN', (app()->runningInConsole() === false) ? request()->getHost() : 'localhost')),

        /*
        |--------------------------------------------------------------------------
        | Laratrust Panel Path
        |--------------------------------------------------------------------------
        |
        | This is the URI path where Laratrust panel for roles and permissions
        | will be accessible from.
        |
        */
        'path' => 'laratrust',

        /*
        |--------------------------------------------------------------------------
        | Laratrust Panel Path
        |--------------------------------------------------------------------------
        |
        | The route where the go back link should point
        |
        */
        'go_back_route' => '/',

        /*
        |--------------------------------------------------------------------------
        | Laratrust Panel Route Middleware
        |--------------------------------------------------------------------------
        |
        | These middleware will get added onto each Laratrust panel route.
        |
        */
        'middleware' => ['web'],

        /*
        |--------------------------------------------------------------------------
        | Enable permissions assignment
        |--------------------------------------------------------------------------
        |
        | Enable/Disable the permissions assignment to the users.
        |
        */
        'assign_permissions_to_user' => true,

        /*
        |--------------------------------------------------------------------------
        | Enable permissions creation
        |--------------------------------------------------------------------------
        |
        | Enable/Disable the possibility to create permissions from the panel.
        |
        */
        'create_permissions' => true,

        /*
        |--------------------------------------------------------------------------
        | Add restriction to roles in the panel
        |--------------------------------------------------------------------------
        |
        | Configure which roles can not be editable, deletable and removable.
        | To add a role to the restriction, use name of the role here.
        |
        */
        'roles_restrictions' => [
            // The user won't be able to remove roles already assigned to users.
            'not_removable' => [],

            // The user won't be able to edit the role and the permissions assigned.
            'not_editable' => [],

            // The user won't be able to delete the role.
            'not_deletable' => [],
        ],
    ]
];
