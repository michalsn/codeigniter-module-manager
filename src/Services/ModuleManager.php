<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Services;

use Exception;
use Michalsn\CodeIgniterModuleManager\Config\ModuleManager as ModuleManagerConfig;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;

class ModuleManager
{
    public function __construct(
        private readonly ModuleManagerConfig $config,
        private readonly ModuleModel $moduleModel,
        private readonly ModuleRegistry $registry,
        private readonly ModuleScanner $scanner,
        private readonly AutoloadManager $autoloadManager,
        private readonly ModuleInstaller $installer,
        private readonly ModuleUpdater $updater,
    ) {
    }

    public function scanAndSyncModules(): array
    {
        $scanResult  = $this->scanner->scanForModules();
        $syncResults = [
            'synced'  => [],
            'failed'  => [],
            'invalid' => $scanResult['invalid'],
            'removed' => [],
        ];

        // Sync found modules
        foreach ($scanResult['valid'] as $moduleData) {
            $result     = $this->moduleModel->upsertByFolderName($moduleData);
            $folderName = $moduleData['folder_name'];

            if ($result) {
                $syncResults['synced'][$folderName] = true;
                log_message('info', "Synced module: {$folderName}");
                // Invalidate cache for this module
                $this->registry->invalidate($folderName);
            } else {
                $syncResults['failed'][$folderName] = false;
                log_message('error', "Failed to sync module: {$folderName}");
            }
        }

        // Remove orphaned modules (in DB but folder doesn't exist)
        $scannedFolders = array_column($scanResult['valid'], 'folder_name');
        $allDbModules   = $this->moduleModel->findAll();

        foreach ($allDbModules as $dbModule) {
            if (! in_array($dbModule->folder_name, $scannedFolders, true)) {
                // Module in DB but folder doesn't exist
                $modulePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $dbModule->folder_name;

                if (! is_dir($modulePath) && $this->moduleModel->delete($dbModule->id)) {
                    $syncResults['removed'][$dbModule->folder_name] = $dbModule->namespace;
                    $this->registry->invalidate($dbModule->folder_name);
                    log_message('info', "Removed orphaned module: {$dbModule->folder_name}");
                }
            }
        }

        // Refresh registry cache after scanning
        $this->registry->refresh();

        return $syncResults;
    }

    public function enableModule(string $namespace): bool
    {
        $module = $this->registry->getByNamespace($namespace);

        if (! $module instanceof Module) {
            throw ModuleException::forModuleNotFound($namespace);
        }

        if (! $module->canBeEnabled()) {
            throw ModuleException::forModuleCannotBeEnabled($namespace);
        }

        return $this->installer->enable($module);
    }

    public function disableModule(string $namespace): bool
    {
        $module = $this->registry->getByNamespace($namespace);

        if (! $module instanceof Module) {
            throw ModuleException::forModuleNotFound($namespace);
        }

        if (! $module->canBeDisabled()) {
            throw ModuleException::forModuleCannotBeDisabled($namespace);
        }

        return $this->installer->disable($module);
    }

    public function updateModule(string $namespace): bool
    {
        $module = $this->registry->getByNamespace($namespace);

        if (! $module instanceof Module) {
            throw ModuleException::forModuleNotFound($namespace);
        }

        if (! $module->is_installed) {
            throw ModuleException::forModuleMustBeInstalledBeforeUpdate();
        }

        if (! $module->isUpdateAvailable()) {
            throw ModuleException::forNoUpdateAvailable($namespace);
        }

        return $this->updater->update($module);
    }

    public function installModule(string $namespace): bool
    {
        $module = $this->registry->getByNamespace($namespace);

        if (! $module instanceof Module) {
            throw ModuleException::forModuleNotFoundScanFirst($namespace);
        }

        if ($module->is_installed) {
            throw ModuleException::forModuleAlreadyInstalled($namespace);
        }

        return $this->installer->install($module);
    }

    public function uninstallModule(string $namespace): bool
    {
        $module = $this->registry->getByNamespace($namespace);

        if (! $module instanceof Module) {
            throw ModuleException::forModuleNotFound($namespace);
        }

        if (! $module->canBeUninstalled()) {
            throw ModuleException::forModuleCannotBeUninstalled($namespace);
        }

        return $this->installer->uninstall($module);
    }

    public function validateModule(string $namespace): array
    {
        $module = $this->registry->getByNamespace($namespace);

        if (! $module instanceof Module) {
            throw ModuleException::forModuleNotFound($namespace);
        }

        return $this->scanner->validateModuleStructure($module->folder_name);
    }

    public function isModuleEnabled(string $namespace): bool
    {
        $module = $this->registry->getByNamespace($namespace);

        return $module && $module->is_enabled;
    }

    public function isModuleInstalled(string $namespace): bool
    {
        $module = $this->registry->getByNamespace($namespace);

        return $module && $module->is_installed;
    }

    public function checkDependencies(string $namespace): array
    {
        $missingDependencies = [];

        try {
            $module = $this->registry->getByNamespace($namespace);

            if (! $module instanceof Module) {
                throw ModuleException::forModuleNotFound($namespace);
            }

            // Load module instance to check dependencies
            $moduleFilePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php';
            require_once $moduleFilePath;

            $className = $namespace . '\\Module';
            $instance  = new $className();

            $dependencies = $instance->getDependencies();

            foreach ($dependencies as $dependency) {
                if (! $this->isModuleEnabled($dependency)) {
                    $missingDependencies[] = $dependency;
                }
            }
        } catch (Exception $e) {
            log_message('error', "Failed to check dependencies for {$namespace}: " . $e->getMessage());
        }

        return $missingDependencies;
    }

    /**
     * Manually regenerate the modules autoload file
     * Useful for recovery or after manual database changes
     */
    public function regenerateAutoload(): bool
    {
        try {
            $enabledModules = $this->moduleModel->getEnabled();
            $modulesData    = [];

            foreach ($enabledModules as $module) {
                if (! empty($module->namespace)) {
                    // PSR-4 path points to src/ folder
                    $modulePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src';

                    $modulesData[] = [
                        'namespace' => $module->namespace,
                        'path'      => $modulePath,
                    ];
                }
            }

            $this->autoloadManager->regenerateAutoloadFile($modulesData);

            log_message('info', 'Modules autoload file regenerated successfully');

            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to regenerate autoload: ' . $e->getMessage());

            throw ModuleException::forFailedToRegenerateAutoload($e->getMessage());
        }
    }

    /**
     * Get the path to the modules autoload file
     */
    public function getAutoloadFilePath(): string
    {
        return $this->autoloadManager->getAutoloadFilePath();
    }

    /**
     * Check if the modules autoload file exists
     */
    public function autoloadFileExists(): bool
    {
        return $this->autoloadManager->autoloadFileExists();
    }

    /**
     * Get statistics about modules
     */
    public function getModuleStats(): array
    {
        $allModules = $this->moduleModel->findAll();
        $installed  = 0;
        $enabled    = 0;
        $updatable  = 0;

        foreach ($allModules as $module) {
            if ($module->is_installed) {
                $installed++;
            }
            if ($module->is_enabled) {
                $enabled++;
            }
            if ($module->isUpdateAvailable()) {
                $updatable++;
            }
        }

        return [
            'total'     => count($allModules),
            'installed' => $installed,
            'enabled'   => $enabled,
            'updatable' => $updatable,
            'disabled'  => $installed - $enabled,
        ];
    }
}
