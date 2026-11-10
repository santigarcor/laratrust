<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Workbench\App\Models\Permission;
use Workbench\App\Models\Role;
use Workbench\App\Models\Team;
use Workbench\App\Models\User;

/**
 * Demo data for the admin panel: run "composer serve" and open /laratrust.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            'users-create' => 'Create users',
            'users-read' => 'Read users',
            'users-update' => 'Update users',
            'users-delete' => 'Delete users',
            'posts-create' => 'Create posts',
            'posts-read' => 'Read posts',
            'posts-update' => 'Update posts',
            'posts-delete' => 'Delete posts',
            'billing-manage' => 'Manage billing',
            'reports-export' => 'Export reports',
            'settings-update' => 'Update settings',
            'audit-read' => 'Read the audit log',
        ])->map(fn (string $displayName, string $name) => Permission::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => "Allows to {$displayName}.",
        ]));

        // Locked in workbench/config/laratrust.php: it can't be edited, deleted or removed.
        $superadmin = Role::create(['name' => 'superadmin', 'display_name' => 'Super administrator', 'description' => 'Can do everything.']);
        $superadmin->syncPermissions($permissions->values()->all());

        $editor = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'description' => 'Writes and publishes posts.']);
        $editor->syncPermissions($permissions->only(['posts-create', 'posts-read', 'posts-update'])->values()->all());

        $support = Role::create(['name' => 'support', 'display_name' => 'Support agent', 'description' => 'Helps the users.']);
        $support->syncPermissions($permissions->only(['users-read', 'posts-read'])->values()->all());

        Role::create(['name' => 'billing', 'display_name' => 'Billing']);

        $team = Team::create(['name' => 'acme', 'display_name' => 'Acme']);

        $user = fn (string $name, string $email) => User::create(['name' => $name, 'email' => $email, 'password' => Hash::make('password')]);

        $user('Ada Lovelace', 'ada@example.com')->addRole($superadmin)->addRole($editor)->givePermission('reports-export');
        $user('Grace Hopper', 'grace@example.com')->addRole($editor)->addRole($support, $team);
        $user('Alan Turing', 'alan@example.com');
    }
}
