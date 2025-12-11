<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Services;

use CodeIgniter\CodeIgniter;
use Config\Services;
use Exception;
use Michalsn\CodeIgniterModuleManager\Config\ModuleManager as ModuleManagerConfig;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;
use Michalsn\CodeIgniterModuleManager\Traits\LoadsModuleInstance;

class ModuleInstaller
{
    use LoadsModuleInstance;

    public function __construct(
        private readonly ModuleManagerConfig $config,
        private readonly ModuleModel $moduleModel,
        private readonly AutoloadManager $autoloadManager,
        private readonly ModuleRegistry $registry,
        private readonly RequirementsValidator $requirementsValidator,
    ) {
    }

    public function install(Module $module): bool
    {
        if ($module->is_installed) {
            return true;
        }

        try {
            // Validate requirements before installation
            $this->requirementsValidator->validate($module);

            // Temporarily register namespace for migration autoloading
            // This registration only lasts for the current request
            if (! empty($module->namespace)) {
                $modulePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src';
                $autoloader = service('autoloader');
                $autoloader->addNamespace($module->namespace, $modulePath);

                log_message('debug', "Temporarily registered namespace {$module->namespace} for installation");

                // Run install migrations (database setup)
                $this->runInstallMigrations($module);
            }

            // Call module's onInstall method
            $this->callModuleMethod($module, 'onInstall');

            // Mark as installed in database
            $success = $this->moduleModel->markAsInstalled($module->id, $module->version);

            if (! $success) {
                throw ModuleException::forFailedToMarkAsInstalled();
            }

            // Invalidate cache for this module
            $this->registry->invalidate($module->folder_name);

            log_message('info', "Module installed: {$module->folder_name}");

            return true;
        } catch (Exception $e) {
            log_message('error', "Failed to install module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forInstallationFailed($e->getMessage());
        }
    }

    public function uninstall(Module $module): bool
    {
        if (! $module->is_installed) {
            return true;
        }

        if ($module->is_enabled) {
            throw ModuleException::forCannotUninstallEnabledModule();
        }

        try {
            // Call module's onUninstall method before rollback
            $this->callModuleMethod($module, 'onUninstall');

            // Rollback all module migrations after cleanup
            if (! empty($module->namespace)) {
                $this->rollbackAllModuleMigrations($module);
            }

            // Mark as uninstalled in database
            $success = $this->moduleModel->markAsUninstalled($module->id);

            if (! $success) {
                throw ModuleException::forFailedToMarkAsUninstalled();
            }

            // Invalidate cache for this module
            $this->registry->invalidate($module->folder_name);

            log_message('info', "Module uninstalled: {$module->folder_name}");

            return true;
        } catch (Exception $e) {
            log_message('error', "Failed to uninstall module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forUninstallationFailed($e->getMessage());
        }
    }

    public function enable(Module $module): bool
    {
        if (! $module->is_installed) {
            throw ModuleException::forModuleMustBeInstalled();
        }

        if ($module->is_enabled) {
            return true;
        }

        try {
            // Mark as enabled in database
            $success = $this->moduleModel->markAsEnabled($module->id);

            if (! $success) {
                throw ModuleException::forFailedToMarkAsEnabled();
            }

            // Regenerate the autoload file with all enabled modules
            $this->regenerateModulesAutoload();

            // Call module's onEnable method
            $this->callModuleMethod($module, 'onEnable');

            // Invalidate cache for this module
            $this->registry->invalidate($module->folder_name);

            log_message('info', "Module enabled: {$module->folder_name}");

            return true;
        } catch (Exception $e) {
            // Rollback database changes
            $this->moduleModel->markAsDisabled($module->id);

            // Regenerate autoload to remove the module
            try {
                $this->regenerateModulesAutoload();
            } catch (Exception $autoloadException) {
                log_message('error', 'Failed to rollback autoload after enable failure: ' . $autoloadException->getMessage());
            }

            log_message('error', "Failed to enable module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forEnableFailed($e->getMessage());
        }
    }

    public function disable(Module $module): bool
    {
        if (! $module->is_enabled) {
            return true;
        }

        try {
            // Call module's onDisable method first
            $this->callModuleMethod($module, 'onDisable');

            // Mark as disabled in database
            $success = $this->moduleModel->markAsDisabled($module->id);

            if (! $success) {
                throw ModuleException::forFailedToMarkAsDisabled();
            }

            // Regenerate the autoload file without this module
            $this->regenerateModulesAutoload();

            // Invalidate cache for this module
            $this->registry->invalidate($module->folder_name);

            log_message('info', "Module disabled: {$module->folder_name}");

            return true;
        } catch (Exception $e) {
            log_message('error', "Failed to disable module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forDisableFailed($e->getMessage());
        }
    }

    /**
     * Regenerate the modules autoload file based on currently enabled modules
     */
    private function regenerateModulesAutoload(): void
    {
        $enabledModules = $this->moduleModel->getEnabled();
        $modulesData    = [];

        foreach ($enabledModules as $module) {
            if (! empty($module->namespace)) {
                // PSR-4 path points to src/ folder
                $modulePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src';

                // Check if module has bundled dependencies
                $hasBundledDeps = false;

                try {
                    $moduleInstance = $this->loadModuleInstance($module);
                    $hasBundledDeps = $moduleInstance->hasBundledDependencies();
                } catch (Exception $e) {
                    log_message('warning', "Could not check bundled dependencies for {$module->folder_name}: " . $e->getMessage());
                }

                $modulesData[] = [
                    'namespace'        => $module->namespace,
                    'path'             => $modulePath,
                    'has_bundled_deps' => $hasBundledDeps,
                ];
            }
        }

        $this->autoloadManager->regenerateAutoloadFile($modulesData);
    }

    /**
     * Run install migrations for a module
     * Uses CodeIgniter's migration service to run all pending migrations
     */
    private function runInstallMigrations(Module $module): void
    {
        try {
            $migrate = Services::migrations();
            $migrate->setNamespace($module->namespace);

            // Run latest migrations for this namespace
            if ($migrate->latest()) {
                log_message('info', "Ran install migrations for module: {$module->folder_name}");
            } else {
                $errors = $migrate->getCliMessages();

                // If no migrations exist, that's okay
                if (empty($errors) || str_contains(strtolower(implode(' ', $errors)), strtolower('no migrations found'))) {
                    log_message('info', "No migrations found for module: {$module->folder_name}");
                } else {
                    throw ModuleException::forMigrationFailed(implode(', ', $errors));
                }
            }
        } catch (Exception $e) {
            log_message('error', "Failed to run install migrations for module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forMigrationFailed($e->getMessage());
        }
    }

    /**
     * Rollback all migrations for a module using force() to avoid gap detection
     */
    private function rollbackAllModuleMigrations(Module $module): void
    {
        try {
            // Temporarily register namespace for migration rollback
            $modulePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src';
            $autoloader = service('autoloader');
            $autoloader->addNamespace($module->namespace, $modulePath);

            log_message('debug', "Temporarily registered namespace {$module->namespace} for uninstallation");

            // Get migration runner
            $migrate = Services::migrations();
            $migrate->setNamespace($module->namespace);

            // Find all migration files for this namespace
            $migrations = $migrate->findMigrations();

            if (empty($migrations)) {
                log_message('info', "No migrations found for module: {$module->folder_name}");

                return;
            }

            // Sort migrations in reverse order (newest first) for rollback
            $migrationsReversed = array_reverse($migrations);

            // Force each migration down (force auto-detects applied state and runs down())
            foreach ($migrationsReversed as $migration) {
                try {
                    $migrate->force($migration->path, $migration->namespace);
                    log_message('info', "Rolled back migration: {$migration->class}");
                } catch (Exception $e) {
                    log_message('error', "Failed to rollback migration {$migration->class}: " . $e->getMessage());

                    throw ModuleException::forMigrationRollbackFailed($migration->class, $e->getMessage());
                }
            }

            log_message('info', "Successfully rolled back all migrations for module: {$module->folder_name}");
        } catch (Exception $e) {
            log_message('error', "Failed to rollback migrations for module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forMigrationRollbackGeneralFailure($e->getMessage());
        }
    }

    private function callModuleMethod(Module $module, string $method, array $args = []): void
    {
        try {
            if (! $module->namespace) {
                return; // No namespace, can't call method
            }

            $instance = $this->loadModuleInstance($module);

            if (method_exists($instance, $method)) {
                call_user_func_array([$instance, $method], $args);
            }
        } catch (Exception $e) {
            log_message('error', "Failed to call {$method} on module {$module->folder_name}: " . $e->getMessage());
            // Don't throw exception here as it might break the main operation
        }
    }
}
