<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

class ModuleInstall extends BaseModuleCommand
{
    protected $name        = 'module:install';
    protected $description = 'Install a module (runs migrations)';
    protected $usage       = 'module:install <module>';
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

        CLI::write("Installing module: {$namespace}", 'yellow');

        try {
            $success = $this->moduleManager->installModule($namespace);

            if ($success) {
                CLI::write("Module '{$namespace}' installed successfully.", 'green');

                return EXIT_SUCCESS;
            }

            CLI::error("Failed to install module '{$namespace}'.");

            return EXIT_ERROR;
        } catch (ModuleException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }
    }
}
