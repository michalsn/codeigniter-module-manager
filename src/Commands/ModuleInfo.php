<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;
use Michalsn\CodeIgniterModuleManager\Entities\Module;

class ModuleInfo extends BaseModuleCommand
{
    protected $name        = 'module:info';
    protected $description = 'Show module information';
    protected $usage       = 'module:info <module>';
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

        $module = $this->moduleRegistry->getByNamespace($namespace);

        if (! $module instanceof Module) {
            CLI::error("Module '{$namespace}' not found.");

            return EXIT_ERROR;
        }

        CLI::write("Module Information: {$module->getDisplayName()}", 'yellow');
        CLI::write('');

        $thead   = ['Property', 'Value'];
        $tbody   = [];
        $tbody[] = ['Name', $module->name];
        $tbody[] = ['Description', $module->description];
        $tbody[] = ['Namespace', $module->namespace];
        $tbody[] = ['Version', $module->version];
        $tbody[] = ['Author', $module->author];
        $tbody[] = ['URL', $module->url ?: 'N/A'];
        $tbody[] = ['Folder', $module->folder_name];
        $tbody[] = ['Installed', $module->is_installed ? 'Yes' : 'No'];
        $tbody[] = ['Enabled', $module->is_enabled ? 'Yes' : 'No'];
        $tbody[] = ['Installed Version', $module->installed_version ?: 'N/A'];
        $tbody[] = ['Update Available', $module->isUpdateAvailable() ? 'Yes' : 'No'];

        CLI::table($tbody, $thead);

        // Show dependencies
        $dependencies = $this->moduleManager->checkDependencies($module->namespace);
        if ($dependencies !== []) {
            CLI::write('');
            CLI::write('Missing Dependencies:', 'red');

            foreach ($dependencies as $dependency) {
                CLI::write("  - {$dependency}", 'red');
            }
        }

        return EXIT_SUCCESS;
    }
}
