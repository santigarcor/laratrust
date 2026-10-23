<?php

declare(strict_types=1);

namespace Laratrust\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

#[Description('Setup migration and models for Laratrust')]
class SetupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:setup';

    /**
     * Commands to call with their description.
     *
     * @var array<string, string>
     */
    protected $calls = [
        'laratrust:migration' => 'Creating migration',
        'laratrust:role' => 'Creating Role model',
        'laratrust:permission' => 'Creating Permission model',
    ];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        if (Config::get('laratrust.teams.enabled')) {
            $this->calls['laratrust:team'] = 'Creating Team model';
        }

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        foreach ($this->calls as $command => $info) {
            $this->line(PHP_EOL.$info);
            $this->call($command);
        }
    }
}
