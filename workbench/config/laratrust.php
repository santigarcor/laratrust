<?php

declare(strict_types=1);

use Workbench\App\Models\Permission;
use Workbench\App\Models\Role;
use Workbench\App\Models\Team;
use Workbench\App\Models\User;

// Workbench replaces the whole "laratrust" config with this file, so it starts from the package's one.
return array_replace_recursive(require __DIR__.'/../../config/laratrust.php', [
    'user_models' => ['users' => User::class],
    'models' => [
        'role' => Role::class,
        'permission' => Permission::class,
        'team' => Team::class,
    ],
    'teams' => ['enabled' => true],
    'panel' => [
        'register' => true,
        'roles_restrictions' => [
            'not_removable' => ['superadmin'],
            'not_editable' => ['superadmin'],
            'not_deletable' => ['superadmin'],
        ],
    ],
]);
