<?php

declare(strict_types=1);

namespace Laratrust\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Throwable;

#[Description('Creates the seeder following the Laratrust specifications.')]
#[Signature('laratrust:seeder')]
class MakeSeederCommand extends Command
{
    /**
     * Create database/seeders/LaratrustSeeder.php from the stub, using the configured models and tables.
     */
    public function handle(Filesystem $files): int
    {
        $path = $this->laravel->databasePath('seeders/LaratrustSeeder.php');

        if ($files->exists($path)) {
            $this->components->warn('The LaratrustSeeder file already exists. Delete the existing one if you want to create a new one.');

            return self::SUCCESS;
        }

        try {
            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, $this->buildSeeder($files->get(__DIR__.'/../../stubs/seeder.stub')));
        } catch (Throwable) {
            $this->components->error("Couldn't create the seeder. Check the write permissions within the ".dirname($path).' directory.');

            return self::FAILURE;
        }

        $this->components->info('Seeder successfully created!');

        return self::SUCCESS;
    }

    private function buildSeeder(string $stub): string
    {
        $userModels = Config::get('laratrust.user_models');

        return strtr($stub, [
            '{{roleConfiguredModelClass}}'           => $this->fullyQualified(Config::get('laratrust.models.role')),
            '{{permissionConfiguredModelClass}}'     => $this->fullyQualified(Config::get('laratrust.models.permission')),
            '{{userConfiguredModelClass}}'           => $this->fullyQualified(reset($userModels)),
            '{{permission_roleConfiguredTableName}}' => Config::get('laratrust.tables.permission_role'),
            '{{permission_userConfiguredTableName}}' => Config::get('laratrust.tables.permission_user'),
            '{{role_userConfiguredTableName}}'       => Config::get('laratrust.tables.role_user'),
            '{{rolesTableName}}'                     => Config::get('laratrust.tables.roles'),
            '{{permissionsTableName}}'               => Config::get('laratrust.tables.permissions'),
        ]);
    }

    private function fullyQualified(string $class): string
    {
        return '\\'.ltrim($class, '\\');
    }
}
