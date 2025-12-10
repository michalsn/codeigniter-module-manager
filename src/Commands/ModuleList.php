<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;

class ModuleList extends BaseModuleCommand
{
    protected $name        = 'module:list';
    protected $description = 'List all modules';
    protected $usage       = 'module:list';

    public function run(array $params)
    {
        $this->initializeServices();

        $modules = $this->moduleRegistry->all();

        if ($modules === []) {
            CLI::write('No modules found. Run "php spark module:scan" to scan for modules.', 'yellow');

            return EXIT_SUCCESS;
        }

        $thead = ['Name', 'Namespace', 'Version', 'Installed', 'Enabled', 'Update Available', 'Installed Version'];
        $tbody = [];

        foreach ($modules as $module) {
            $tbody[] = [
                $module->getDisplayName(),
                $module->getFullNamespace(),
                $module->version,
                $module->is_installed ? 'Yes' : 'No',
                $module->is_enabled ? 'Yes' : 'No',
                $module->isUpdateAvailable() ? 'Yes' : 'No',
                $module->installed_version ?? 'N/A',
            ];
        }

        CLI::table($tbody, $thead);

        return EXIT_SUCCESS;
    }
}
