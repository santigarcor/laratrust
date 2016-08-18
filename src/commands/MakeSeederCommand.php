<?php

namespace Laratrust;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MakeSeederCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the seeder following the Laratrust specifications.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel->view->addNamespace('laratrust', substr(__DIR__, 0, -8).'views');

        if ($this->createSeeder()) {
            $this->info("Seeder successfully created!");
        } else {
            $this->error(
                "Couldn't create seeder.\n".
                "Check the write permissions within the database/seeds directory."
            );
        }

        $this->line('');
    }

    /**
     * Create the seeder
     * @return bool
     */
    protected function createSeeder()
    {
        $permission = Config::get('laratrust.permission', 'App\Permission');
        $role = Config::get('laratrust.role', 'App\Role');
        $rolePermissions = Config::get('laratrust.permission_role_table');
        $roleUsers = Config::get('laratrust.role_user_table');
        $user   = Config::get('auth.providers.users.model', 'App\User');

        $migrationPath = $this->getMigrationPath();
        $output = $this->laravel->view->make('laratrust::generators.seeder')
            ->with(compact([
                'role',
                'permission',
                'user',
                'rolePermissions',
                'roleUsers',
            ]))
            ->render();

        if (!file_exists($migrationPath) && $fs = fopen($migrationPath, 'x')) {
            fwrite($fs, $output);
            fclose($fs);
            return true;
        }

        return false;
    }

    /**
     * Get the seeder path.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return database_path("seeds/LaratrustSeeder.php");
    }
}
