<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Publisher\Publisher;
use Throwable;

class ModulePublish extends BaseCommand
{
    protected $group       = 'Modules';
    protected $name        = 'module:publish';
    protected $description = 'Publish Module Manager config file and modify Autoload to support modules.';

    public function run(array $params)
    {
        $source = service('autoloader')->getNamespace('Michalsn\\CodeIgniterModuleManager')[0];

        $publisher = new Publisher($source, APPPATH);

        try {
            $publisher->addPaths([
                'Config/ModuleManager.php',
            ])->merge(false);
        } catch (Throwable $e) {
            $this->showError($e);

            return;
        }

        // Update published config file
        foreach ($publisher->getPublished() as $file) {
            $publisher->replace(
                $file,
                [
                    'namespace Michalsn\\CodeIgniterModuleManager\\Config' => 'namespace Config',
                    'use CodeIgniter\\Config\\BaseConfig'                  => 'use Michalsn\\CodeIgniterModuleManager\\Config\\ModuleManager as BaseModuleManager',
                    'class ModuleManager extends BaseConfig'               => 'class ModuleManager extends BaseModuleManager',
                ],
            );
        }

        CLI::write(CLI::color('  Config Published! ', 'green') . 'You can customize the configuration by editing the "app/Config/ModuleManager.php" file.');
        CLI::newLine();

        // Modify Autoload.php to add constructor
        $this->modifyAutoload();
    }

    private function modifyAutoload(): void
    {
        $autoloadFile = APPPATH . 'Config/Autoload.php';

        if (! file_exists($autoloadFile)) {
            CLI::error('Autoload.php file not found at: ' . $autoloadFile);

            return;
        }

        $contents = file_get_contents($autoloadFile);

        // Check if constructor already exists
        if (str_contains($contents, 'public function __construct()')) {
            CLI::write(CLI::color('  Autoload Already Modified! ', 'yellow') . 'Constructor already exists in Autoload.php');

            return;
        }

        // Find the class definition and add constructor after it
        $constructorCode = <<<'CODE'

                /**
                 * Loads enabled modules from modules_psr4.php
                 */
                public function __construct()
                {
                    parent::__construct();

                    $modulesPsr4 = WRITEPATH . 'modules_psr4.php';

                    if (file_exists($modulesPsr4)) {
                        $modules = include $modulesPsr4;

                        if (is_array($modules)) {
                            foreach ($modules as $namespace => $path) {
                                $this->psr4[$namespace] = $path;
                            }
                        }
                    }
                }
            CODE;

        // Insert constructor after the class properties (after the closing bracket of $helpers array)
        $pattern = '/(public\s+\$helpers\s*=\s*\[[^\]]*\];)/s';

        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, "$1\n" . $constructorCode, $contents);
            file_put_contents($autoloadFile, $contents);

            CLI::write(CLI::color('  Autoload Modified! ', 'green') . 'Constructor added to load enabled modules automatically.');
        } else {
            CLI::error('Could not find the right place to insert constructor in Autoload.php');
            CLI::write('Please manually add the constructor to Config\\Autoload.php class:', 'yellow');
            CLI::write($constructorCode);
        }
    }
}
