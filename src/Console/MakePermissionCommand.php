<?php

declare(strict_types=1);

namespace Laratrust\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Config;

#[Description('Create Permission model if it does not exist')]
class MakePermissionCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laratrust:permission';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Permission model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../../stubs/permission.stub';
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return Config::get('laratrust.models.permission', 'Permission');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    /**
     * The class name comes from the config, so the generator's "name" argument isn't needed.
     *
     * @return array<int, mixed>
     */
    protected function getArguments(): array
    {
        return [];
    }
}
