<?php

declare(strict_types=1);

return [
    /**
     * Control if all the laratrust tables should be truncated before running the seeder.
     */
    'truncate_tables' => true,

    'roles_permissions' => [
        // // Examples:
        // 'superadmin' => [                    // We have a super admin,
        //     'users' => ['c','r','u','d'],    // that can create-users, read-users, ...
        //     'posts' => ['c','r','u','d'],    // that can create-posts, read-posts, ...
        // ],

        // 'user' => [                          // We have an user,
        //     'users' => ['r'],                // that only can read-users.
        //     'posts' => ['c','r'],            // that only can create-posts and read-posts.
        // ],
    ],

    'actions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete',
    ],
];
