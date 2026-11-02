<?php

declare(strict_types=1);

namespace Laratrust\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Laratrust\Console\Concerns\CreatesLaratrustMigrations;

#[Description('Setup the teams feature in case it is not used')]
class SetupTeamsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:setup-teams';

    use CreatesLaratrustMigrations;

    protected string $migrationSuffix = 'laratrust_setup_teams';

    /** @var view-string */
    protected string $migrationView = 'laratrust::setup-teams';

    protected string $migrationName = 'Laratrust setup teams migration';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!Config::get('laratrust.teams.enabled')) {
            $this->error('Not using teams in your Laratrust configuration file.');
            $this->warn('Please enable the teams usage in your configuration.');

            return;
        }

        $this->line('');
        $this->info('The Laratrust teams feature setup is going to add a migration and a model');

        $existingMigrations = $this->alreadyExistingMigrations();

        if ($existingMigrations !== []) {
            $this->line('');

            $this->warn($this->getExistingMigrationsWarning($existingMigrations));
        }

        $this->line('');

        if (!$this->confirm('Proceed with the migration creation?', true)) {
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

        $this->line('Creating Team model');
        $this->call('laratrust:team');

        $this->line('');
    }
}
