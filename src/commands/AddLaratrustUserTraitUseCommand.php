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
use Laratrust\Traits\LaratrustUserTrait;
use Traitor\Traitor;

class AddLaratrustUserTraitUseCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:add-trait';

    /**
     * Trait added to User model
     *
     * @var string
     */
    protected $targetTrait = LaratrustUserTrait::class;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $userModel = $this->getUserModel();
        
        if (! class_exists($userModel)) {
            $this->error("Class $userModel does not exist.");
            return;
        }

        if ($this->alreadyUsesLaratrustUserTrait()) {
            $this->error("Class $userModel already uses LaratrustUserTrait.");
            return;
        }

        Traitor::addTrait($this->targetTrait)->toClass($userModel);

        $this->info("LaratrustUserTrait added successfully");
    }

    /**
     * @return bool
     */
    protected function alreadyUsesLaratrustUserTrait()
    {
        return in_array(LaratrustUserTrait::class, class_uses($this->getUserModel()));
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return "Add LaratrustUserTrait to {$this->getUserModel()} class";
    }

    /**
     * @return string
     */
    protected function getUserModel()
    {
        return Config::get('auth.providers.users.model', 'App\User');
    }
}
