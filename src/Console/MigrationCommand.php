<?php

declare(strict_types=1);

namespace Laratrust\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Laratrust\Console\Concerns\CreatesLaratrustMigrations;

#[Description('Creates a migration following the Laratrust specifications.')]
class MigrationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:migration';

    use CreatesLaratrustMigrations;

    protected string $migrationSuffix = 'laratrust_setup_tables';

    /** @var view-string */
    protected string $migrationView = 'laratrust::migration';

    protected string $migrationName = 'Laratrust migration';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->line('');
        $this->info('Laratrust Migration Creation.');
        if (Config::get('laratrust.teams.enabled')) {
            $this->comment('You are using the teams feature.');
        }
        $this->line('');
        $this->comment($this->generateMigrationMessage());

        $existingMigrations = $this->alreadyExistingMigrations();
        $defaultAnswer = true;

        if ($existingMigrations !== []) {
            $this->line('');

            $this->warn($this->getExistingMigrationsWarning($existingMigrations));

            $defaultAnswer = false;
        }

        $this->line('');

        if (!$this->confirm('Proceed with the migration creation?', $defaultAnswer)) {
            return;
        }

        $this->line('');

        $this->line('Creating migration');

        if ($this->createMigration()) {
            $this->info('Migration created successfully.');
        } else {
            $this->error(
                "Couldn't create migration.\n".
                'Check the write permissions within the database/migrations directory.'
            );
        }

        $this->line('');
    }

    /**
     * Generate the message to display when running the
     * console command showing what tables are going
     * to be created.
     */
    protected function generateMigrationMessage(): string
    {
        $tables = Collection::make(Config::array('laratrust.tables'))
            ->reject(fn (string $table, string $key): bool => $key === 'teams' && !Config::get('laratrust.teams.enabled'))
            ->sort();

        return "A migration that creates {$tables->implode(', ')} "
            .'tables will be created in database/migrations directory';
    }
}
