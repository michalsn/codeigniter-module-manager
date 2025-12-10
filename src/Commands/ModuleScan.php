<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Commands;

use CodeIgniter\CLI\CLI;

class ModuleScan extends BaseModuleCommand
{
    protected $name        = 'module:scan';
    protected $description = 'Scan for modules and sync database';
    protected $usage       = 'module:scan';

    public function run(array $params)
    {
        $this->initializeServices();

        CLI::write('Scanning for modules...', 'yellow');

        $results = $this->moduleManager->scanAndSyncModules();

        $syncedCount  = count($results['synced']);
        $failedCount  = count($results['failed']);
        $removedCount = count($results['removed']);

        // Show synced modules
        CLI::write("Scan complete: {$syncedCount} modules synced successfully.", 'green');

        foreach ($results['synced'] as $moduleName => $success) {
            CLI::write("  {$moduleName}: OK", 'green');
        }

        // Show failed modules
        if ($failedCount > 0) {
            CLI::write("\n{$failedCount} modules failed to sync:", 'red');

            foreach ($results['failed'] as $moduleName => $success) {
                CLI::write("  {$moduleName}: FAILED", 'red');
            }
        }

        // Show removed orphaned modules
        if ($removedCount > 0) {
            CLI::write("\n{$removedCount} orphaned modules removed from database:", 'yellow');

            foreach ($results['removed'] as $folderName => $namespace) {
                CLI::write("  {$folderName} ({$namespace}): REMOVED", 'yellow');
            }
        }

        return EXIT_SUCCESS;
    }
}
