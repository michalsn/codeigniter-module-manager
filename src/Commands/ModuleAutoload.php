<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

class ModuleAutoload extends BaseModuleCommand
{
    protected $name        = 'module:autoload';
    protected $description = 'Regenerate the modules autoload file';
    protected $usage       = 'module:autoload';

    public function run(array $params)
    {
        $this->initializeServices();

        CLI::write('Regenerating modules autoload file...', 'yellow');

        try {
            $success = $this->moduleManager->regenerateAutoload();

            if ($success) {
                $path = $this->moduleManager->getAutoloadFilePath();
                CLI::write('Autoload file regenerated successfully.', 'green');
                CLI::write("Location: {$path}", 'light_gray');

                return EXIT_SUCCESS;
            }

            CLI::error('Failed to regenerate autoload file.');

            return EXIT_ERROR;
        } catch (ModuleException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }
    }
}
