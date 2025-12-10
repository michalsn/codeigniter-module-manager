<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

class ModuleDisable extends BaseModuleCommand
{
    protected $name        = 'module:disable';
    protected $description = 'Disable a module';
    protected $usage       = 'module:disable <module>';
    protected $arguments   = [
        'module' => 'The module folder name or namespace',
    ];

    public function run(array $params)
    {
        $this->initializeServices();

        $identifier = array_shift($params);

        if (! $identifier) {
            CLI::error('Module identifier is required.');

            return EXIT_ERROR;
        }

        $namespace = $this->resolveNamespace($identifier);

        if (! $namespace) {
            return EXIT_ERROR;
        }

        CLI::write("Disabling module: {$namespace}", 'yellow');

        try {
            $success = $this->moduleManager->disableModule($namespace);

            if ($success) {
                CLI::write("Module '{$namespace}' disabled successfully.", 'green');

                return EXIT_SUCCESS;
            }

            CLI::error("Failed to disable module '{$namespace}'.");

            return EXIT_ERROR;
        } catch (ModuleException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }
    }
}
