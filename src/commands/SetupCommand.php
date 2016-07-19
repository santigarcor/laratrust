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

class SetupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup migration and models for Laratrust';

    /**
     * Commands to call with their description
     *
     * @var array
     */
    protected $calls = [
        'laratrust:migration' => 'Creating migration',
        'laratrust:make-role' => 'Creating Role model',
        'laratrust:make-permission' => 'Creating Permission model',
        'laratrust:add-trait' => 'Adding LaratrustUserTrait to User model'
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        foreach ($this->calls as $command => $info) {
            $this->line(PHP_EOL . $info);
            $this->call($command);
        }
    }
}
