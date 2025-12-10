<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Services\ModuleManager;
use Michalsn\CodeIgniterModuleManager\Services\ModuleRegistry;

abstract class BaseModuleCommand extends BaseCommand
{
    protected $group = 'Modules';
    protected ModuleManager $moduleManager;
    protected ModuleRegistry $moduleRegistry;

    /**
     * Convert user input (folder name or namespace) to namespace
     */
    protected function resolveNamespace(string $identifier): ?string
    {
        if (str_contains($identifier, '\\')) {
            // If identifier contains backslash, it's already a namespace
            $module = $this->moduleRegistry->getByNamespace($identifier);
        } else {
            // Otherwise, it's a folder name - look up the module to get its namespace
            $module = $this->moduleRegistry->getByFolderName($identifier);
        }

        if (! $module instanceof Module) {
            CLI::error("Module not found: {$identifier}");
            CLI::error("Run 'php spark module:scan' to discover modules.", 'yellow');

            return null;
        }

        return $module->namespace;
    }

    /**
     * Initialize services
     */
    protected function initializeServices(): void
    {
        $this->moduleManager  = service('moduleManager');
        $this->moduleRegistry = service('moduleRegistry');
    }
}
