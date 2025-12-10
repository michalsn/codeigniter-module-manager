<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

class ModuleUninstall extends BaseModuleCommand
{
    protected $name        = 'module:uninstall';
    protected $description = 'Uninstall a module (removes DB tables)';
    protected $usage       = 'module:uninstall <module>';
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

        CLI::write("Uninstalling module: {$namespace}", 'yellow');

        try {
            $success = $this->moduleManager->uninstallModule($namespace);

            if ($success) {
                CLI::write("Module '{$namespace}' uninstalled successfully.", 'green');

                return EXIT_SUCCESS;
            }

            CLI::error("Failed to uninstall module '{$namespace}'.");

            return EXIT_ERROR;
        } catch (ModuleException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }
    }
}
