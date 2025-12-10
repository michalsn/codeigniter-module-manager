<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Services;

use Config\Database;
use Config\Services;
use Exception;
use Michalsn\CodeIgniterModuleManager\BaseModule;
use Michalsn\CodeIgniterModuleManager\BaseUpdateManifest;
use Michalsn\CodeIgniterModuleManager\Config\ModuleManager as ModuleManagerConfig;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;

class ModuleUpdater
{
    public function __construct(
        private readonly ModuleManagerConfig $config,
        private readonly ModuleModel $moduleModel,
        private readonly ModuleScanner $scanner,
        private readonly ModuleRegistry $registry,
        private readonly RequirementsValidator $requirementsValidator,
    ) {
    }

    public function update(Module $module): bool
    {
        if (! $module->is_installed) {
            throw ModuleException::forModuleMustBeInstalledBeforeUpdate();
        }

        if (! $module->isUpdateAvailable()) {
            throw ModuleException::forNoUpdateAvailable($module->namespace);
        }

        $fromVersion = $module->installed_version;
        $toVersion   = $module->version;

        try {
            // Validate requirements before updating (new version might have new requirements)
            $this->requirementsValidator->validate($module);

            $manifest = $this->loadUpdateManifest($module);

            if ($manifest instanceof BaseUpdateManifest) {
                // WITH MANIFEST: Run incremental updates through each version
                $this->updateWithManifest($module, $manifest, $fromVersion, $toVersion);
            } else {
                // WITHOUT MANIFEST: Run all latest migrations, then call onUpdate once
                $this->updateWithoutManifest($module, $fromVersion, $toVersion);
            }

            // Update the installed version in database
            $success = $this->moduleModel->update($module->id, [
                'installed_version' => $toVersion,
            ]);

            if (! $success) {
                throw ModuleException::forFailedToUpdateVersionInDatabase();
            }

            // Rescan the module to sync any other changes
            $moduleData = $this->scanner->scanModule($module->folder_name);
            if ($moduleData) {
                $this->moduleModel->upsertByFolderName($moduleData);
            }

            // Invalidate cache for this module
            $this->registry->invalidate($module->folder_name);

            log_message('info', "Module updated: {$module->folder_name} from {$fromVersion} to {$toVersion}");

            return true;
        } catch (Exception $e) {
            log_message('error', "Failed to update module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forUpdateFailed($e->getMessage());
        }
    }

    /**
     * Update WITHOUT manifest: run all latest migrations, then call onUpdate once
     */
    private function updateWithoutManifest(Module $module, string $fromVersion, string $toVersion): void
    {
        // Run all latest migrations for this namespace
        if (! empty($module->namespace)) {
            $migrate = Services::migrations();
            $migrate->setNamespace($module->namespace);

            try {
                if ($migrate->latest()) {
                    log_message('info', "Applied latest migrations for module {$module->folder_name}");
                } else {
                    $errors = $migrate->getCliMessages();
                    if (! empty($errors) && ! str_contains(strtolower(implode(' ', $errors)), strtolower('no migrations found'))) {
                        throw ModuleException::forMigrationFailed(implode(', ', $errors));
                    }
                }
            } catch (Exception $e) {
                log_message('error', "Failed to run migrations for module {$module->folder_name}: " . $e->getMessage());

                throw ModuleException::forMigrationFailed($e->getMessage());
            }
        }

        // Call onUpdate once with from/to versions
        $this->callModuleUpdateMethod($module, $fromVersion, $toVersion);
    }

    /**
     * Update WITH manifest: iterate through each version, running migrations and calling onUpdate for each
     */
    private function updateWithManifest(Module $module, BaseUpdateManifest $manifest, string $fromVersion, string $toVersion): void
    {
        // Get all versions that need to be processed
        $allVersions = $manifest->getAllVersions();

        // Filter to only versions between fromVersion and toVersion
        $versionsToProcess = array_filter($allVersions, static fn ($version) => version_compare($version, $fromVersion, '>') && version_compare($version, $toVersion, '<='));

        // Sort versions in ascending order
        usort($versionsToProcess, version_compare(...));

        if ($versionsToProcess === []) {
            log_message('info', "No intermediate versions to process for {$module->folder_name}");

            return;
        }

        // Process each version incrementally
        $currentVersion = $fromVersion;

        foreach ($versionsToProcess as $targetVersion) {
            log_message('info', "Processing version {$targetVersion} for module {$module->folder_name}");

            // Get migrations for this specific version
            $migrations = $manifest->getMigrationsForVersion($targetVersion);

            // Run migrations for this version (empty array is OK - no migrations to run)
            if ($migrations !== [] && ! empty($module->namespace)) {
                $this->runSpecificMigrations($module, $migrations);
            }

            // Call onUpdate for this version step
            $this->callModuleUpdateMethod($module, $currentVersion, $targetVersion);

            // Move to next version
            $currentVersion = $targetVersion;
        }
    }

    /**
     * Run specific migrations using force() method
     */
    private function runSpecificMigrations(Module $module, array $migrations): void
    {
        $appliedMigrations = $this->getAppliedMigrations($module->namespace);
        $migrationsPath    = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';

        $migrate = Services::migrations();
        $migrate->setNamespace($module->namespace);

        foreach ($migrations as $migrationName) {
            // Skip if already applied
            if (in_array($migrationName, $appliedMigrations, true)) {
                log_message('info', "Migration {$migrationName} already applied, skipping");

                continue;
            }

            // Build full path to migration file
            $migrationFile = $migrationName . '.php';
            $migrationPath = $migrationsPath . DIRECTORY_SEPARATOR . $migrationFile;

            if (! file_exists($migrationPath)) {
                log_message('warning', "Migration file not found: {$migrationPath}");

                continue;
            }

            try {
                // Use force() to run this specific migration
                $migrate->force($migrationPath, $module->namespace);
                log_message('info', "Applied migration: {$migrationName}");
            } catch (Exception $e) {
                log_message('error', "Failed to run migration {$migrationName}: " . $e->getMessage());

                throw ModuleException::forSpecificMigrationFailed($migrationName, $e->getMessage());
            }
        }
    }

    private function loadUpdateManifest(Module $module): ?BaseUpdateManifest
    {
        if (! $module->namespace) {
            return null;
        }

        $manifestClass = $module->namespace . '\\UpdateManifest';

        // Try to load the manifest file from module src/
        $manifestPath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'UpdateManifest.php';

        if (! file_exists($manifestPath)) {
            return null;
        }

        require_once $manifestPath;

        if (! class_exists($manifestClass)) {
            return null;
        }

        $manifest = new $manifestClass();

        if (! $manifest instanceof BaseUpdateManifest) {
            return null;
        }

        return $manifest;
    }

    private function getAppliedMigrations(string $namespace): array
    {
        $db = Database::connect();

        $builder = $db->table('migrations');
        $results = $builder->where('namespace', $namespace)
            ->select('version')
            ->get()
            ->getResultArray();

        return array_column($results, 'version');
    }

    private function callModuleUpdateMethod(Module $module, string $fromVersion, string $toVersion): void
    {
        try {
            if (! $module->namespace) {
                return;
            }

            $moduleFilePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php';

            if (! file_exists($moduleFilePath)) {
                log_message('warning', "Module file not found: {$moduleFilePath}");

                return;
            }

            require_once $moduleFilePath;

            $className = $module->namespace . '\\Module';

            if (! class_exists($className)) {
                log_message('warning', "Module class not found: {$className}");

                return;
            }

            $instance = new $className();

            if (! $instance instanceof BaseModule) {
                log_message('warning', "Module class does not extend BaseModule: {$className}");

                return;
            }

            $instance->onUpdate($fromVersion, $toVersion);
        } catch (Exception $e) {
            log_message('error', "Failed to call onUpdate on module {$module->folder_name}: " . $e->getMessage());

            throw ModuleException::forModuleUpdateMethodFailed($e->getMessage());
        }
    }

    public function checkForUpdates(): array
    {
        $updatableModules = [];
        $installedModules = $this->moduleModel->getInstalled();

        foreach ($installedModules as $module) {
            $moduleData = $this->scanner->scanModule($module->folder_name);

            if ($moduleData && version_compare($moduleData['version'], $module->installed_version, '>')) {
                $updatableModules[] = [
                    'module'            => $module,
                    'current_version'   => $module->installed_version,
                    'available_version' => $moduleData['version'],
                ];
            }
        }

        return $updatableModules;
    }

    public function updateAll(): array
    {
        $results          = [];
        $updatableModules = $this->moduleModel->getUpdatable();

        foreach ($updatableModules as $module) {
            try {
                $result                        = $this->update($module);
                $results[$module->folder_name] = [
                    'success' => $result,
                    'message' => 'Updated successfully',
                ];
            } catch (ModuleException $e) {
                $results[$module->folder_name] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function getMigrationHistory(Module $module): array
    {
        $manifest = $this->loadUpdateManifest($module);

        if (! $manifest instanceof BaseUpdateManifest) {
            return [];
        }

        $appliedMigrations = $this->getAppliedMigrations($module->namespace);
        $history           = [];

        foreach ($manifest->getAllVersions() as $version) {
            $versionMigrations = $manifest->getMigrationsForVersion($version);
            $appliedInVersion  = array_intersect($versionMigrations, $appliedMigrations);

            if ($appliedInVersion !== []) {
                $history[] = [
                    'version'         => $version,
                    'migrations'      => $appliedInVersion,
                    'migration_count' => count($appliedInVersion),
                    'total_expected'  => count($versionMigrations),
                    'is_complete'     => count($appliedInVersion) === count($versionMigrations),
                ];
            }
        }

        return $history;
    }
}
