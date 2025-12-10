<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

class ModuleUpdate extends BaseModuleCommand
{
    protected $name        = 'module:update';
    protected $description = 'Update a module or all modules';
    protected $usage       = 'module:update [module] [--all]';
    protected $arguments   = [
        'module' => 'The module folder name or namespace (optional if using --all)',
    ];
    protected $options = [
        '--all' => 'Update all modules with available updates',
    ];

    public function run(array $params)
    {
        $this->initializeServices();

        $updateAll  = array_key_exists('all', $params) || CLI::getOption('all');
        $identifier = array_shift($params);

        if ($updateAll) {
            return $this->updateAllModules();
        }

        if (! $identifier) {
            CLI::error('Module identifier is required, or use --all flag.');

            return EXIT_ERROR;
        }

        return $this->updateSingleModule($identifier);
    }

    private function updateSingleModule(string $identifier): int
    {
        $namespace = $this->resolveNamespace($identifier);

        if (! $namespace) {
            return EXIT_ERROR;
        }

        CLI::write("Updating module: {$namespace}", 'yellow');

        try {
            $success = $this->moduleManager->updateModule($namespace);

            if ($success) {
                CLI::write("Module '{$namespace}' updated successfully.", 'green');

                return EXIT_SUCCESS;
            }

            CLI::error("Failed to update module '{$namespace}'.");

            return EXIT_ERROR;
        } catch (ModuleException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }
    }

    private function updateAllModules(): int
    {
        CLI::write('Checking for module updates...', 'yellow');

        $updater = service('moduleUpdater');
        $results = $updater->updateAll();

        if ($results === []) {
            CLI::write('No modules require updates.', 'green');

            return EXIT_SUCCESS;
        }

        $hasFailures = false;

        foreach ($results as $moduleName => $result) {
            $status = $result['success'] ? 'SUCCESS' : 'FAILED';
            $color  = $result['success'] ? 'green' : 'red';
            CLI::write("  {$moduleName}: {$status} - {$result['message']}", $color);

            if (! $result['success']) {
                $hasFailures = true;
            }
        }

        return $hasFailures ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
