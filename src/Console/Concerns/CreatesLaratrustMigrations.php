<?php

declare(strict_types=1);

namespace Laratrust\Console\Concerns;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

/**
 * Creates a migration from one of the Laratrust views.
 *
 * The command defines $migrationSuffix (e.g. "laratrust_setup_tables"), $migrationView
 * (e.g. "laratrust::migration") and $migrationName (e.g. "Laratrust migration").
 */
trait CreatesLaratrustMigrations
{
    /**
     * Create the migration, unless a file with the same name exists.
     */
    protected function createMigration(): bool
    {
        $path = $this->getMigrationPath();

        if (File::exists($path)) {
            return false;
        }

        $migration = $this->laravel->make(ViewFactory::class)
            ->make($this->migrationView, ['laratrust' => Config::get('laratrust')])
            ->render();

        return File::put($path, $migration) !== false;
    }

    /**
     * Build a warning about the existing migrations, which could be duplicated.
     *
     * @param non-empty-list<string> $existingMigrations
     */
    protected function getExistingMigrationsWarning(array $existingMigrations): string
    {
        $base = count($existingMigrations) > 1
            ? "{$this->migrationName}s already exist.\nFollowing files were found: "
            : "{$this->migrationName} already exists.\nFollowing file was found: ";

        return $base."\n - ".implode("\n - ", $existingMigrations);
    }

    /**
     * Get the names of the existing migrations with the same suffix.
     *
     * @return list<string>
     */
    protected function alreadyExistingMigrations(): array
    {
        return array_values(array_map(basename(...), File::glob($this->getMigrationPath('*'))));
    }

    /**
     * Get the migration path, for now or for the given date (which can be a wildcard).
     */
    protected function getMigrationPath(?string $date = null): string
    {
        $date ??= date('Y_m_d_His');

        return $this->laravel->databasePath("migrations/{$date}_{$this->migrationSuffix}.php");
    }
}
