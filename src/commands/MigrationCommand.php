<?php

namespace Santigarcor\Laratrust;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Santigarcor\Laratrust
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MigrationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a migration following the Laratrust specifications.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel->view->addNamespace('laratrust', substr(__DIR__, 0, -8).'views');

        $rolesTable          = Config::get('laratrust.roles_table');
        $roleUserTable       = Config::get('laratrust.role_user_table');
        $permissionsTable    = Config::get('laratrust.permissions_table');
        $permissionRoleTable = Config::get('laratrust.permission_role_table');

        $this->line('');
        $this->info("Tables: $rolesTable, $roleUserTable, $permissionsTable, $permissionRoleTable");

        $message = "A migration that creates '$rolesTable', '$roleUserTable', '$permissionsTable', '$permissionRoleTable'".
        " tables will be created in database/migrations directory";

        $this->comment($message);
        $this->line('');

        if ($this->confirm("Proceed with the migration creation? [Yes|no]", "Yes")) {
            $this->line('');

            $this->info("Creating migration...");
            if ($this->createMigration($rolesTable, $roleUserTable, $permissionsTable, $permissionRoleTable)) {
                $this->info("Migration successfully created!");
            } else {
                $this->error(
                    "Couldn't create migration.\n Check the write permissions".
                    " within the database/migrations directory."
                );
            }

            $this->line('');
        }
    }

    /**
     * Create the migration.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function createMigration($rolesTable, $roleUserTable, $permissionsTable, $permissionRoleTable)
    {
        $migrationFile = base_path("/database/migrations")."/".date('Y_m_d_His')."_laratrust_setup_tables.php";

        $usersTable  = Config::get('auth.providers.users.table') ?: 'users';
        $userModel   = Config::get('auth.providers.users.model');
        $userKeyName = (new $userModel())->getKeyName();

        $data = compact('rolesTable', 'roleUserTable', 'permissionsTable', 'permissionRoleTable', 'usersTable', 'userKeyName');

        $output = $this->laravel->view->make('laratrust::generators.migration')->with($data)->render();

        if (!file_exists($migrationFile) && $fs = fopen($migrationFile, 'x')) {
            fwrite($fs, $output);
            fclose($fs);
            return true;
        }

        return false;
    }
}
