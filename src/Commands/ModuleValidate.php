<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;

class ModuleValidate extends BaseModuleCommand
{
    protected $name        = 'module:validate';
    protected $description = 'Validate module structure';
    protected $usage       = 'module:validate <module>';
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

        CLI::write("Validating module: {$namespace}", 'yellow');

        $errors = $this->moduleManager->validateModule($namespace);

        if ($errors === []) {
            CLI::write("Module '{$namespace}' is valid.", 'green');

            return EXIT_SUCCESS;
        }

        CLI::write("Module '{$namespace}' has validation errors:", 'red');

        foreach ($errors as $error) {
            CLI::write("  - {$error}", 'red');
        }

        return EXIT_ERROR;
    }
}
