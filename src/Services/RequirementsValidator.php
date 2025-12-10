<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Services;

use CodeIgniter\CodeIgniter;
use Michalsn\CodeIgniterModuleManager\BaseModule;
use Michalsn\CodeIgniterModuleManager\Config\ModuleManager as ModuleManagerConfig;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

class RequirementsValidator
{
    public function __construct(
        private readonly ModuleManagerConfig $config,
        private readonly ModuleRegistry $registry,
    ) {
    }

    /**
     * Validate module requirements (CodeIgniter version and dependencies)
     */
    public function validate(Module $module): void
    {
        $instance = $this->loadModuleInstance($module);

        // Check CodeIgniter version requirement
        $requiredVersion = $instance->getRequiredCIVersion();

        if ($requiredVersion !== null) {
            $this->validateCodeIgniterVersion($requiredVersion);
        }

        // Check module dependencies
        $dependencies = $instance->getDependencies();

        if ($dependencies !== []) {
            $this->validateDependencies($dependencies);
        }
    }

    /**
     * Validate CodeIgniter version requirement
     */
    private function validateCodeIgniterVersion(array|string $requiredVersion): void
    {
        $currentVersion = CodeIgniter::CI_VERSION;

        if (is_string($requiredVersion)) {
            // String format: minimum version only
            if (version_compare($currentVersion, $requiredVersion, '<')) {
                throw ModuleException::forRequiresNewerCodeIgniterVersion($requiredVersion, $currentVersion);
            }
        } elseif (is_array($requiredVersion)) {
            // Array format: [min, max] version range
            if (count($requiredVersion) !== 2) {
                throw ModuleException::forInvalidVersionRangeFormat();
            }

            [$minVersion, $maxVersion] = $requiredVersion;

            if (version_compare($currentVersion, $minVersion, '<') || version_compare($currentVersion, $maxVersion, '>')) {
                throw ModuleException::forRequiresCodeIgniterVersionRange($minVersion, $maxVersion, $currentVersion);
            }
        }
    }

    /**
     * Validate module dependencies
     */
    private function validateDependencies(array $dependencies): void
    {
        $missingDependencies = [];

        foreach ($dependencies as $key => $value) {
            // Determine if this is a simple list or an associative array
            // List format: [0 => 'Modules\\Users', 1 => 'Modules\\Posts']
            // Associative format: ['Modules\\Users' => '1.0.0', 'Modules\\Posts' => ['1.0.0', '2.0.0']]
            if (is_int($key)) {
                // Simple list format - $value is the namespace
                $dependencyNamespace = $value;
                $requiredVersion     = null;
            } else {
                // Associative format - $key is namespace, $value is version requirement
                $dependencyNamespace = $key;
                $requiredVersion     = $value;
            }

            // Get the dependency module from registry
            $dependencyModule = $this->registry->get($dependencyNamespace);

            if ($dependencyModule === null) {
                $missingDependencies[] = $dependencyNamespace . ' (not found)';

                continue;
            }

            if (! $dependencyModule->is_installed) {
                throw ModuleException::forDependencyNotInstalled($dependencyNamespace);
            }

            if (! $dependencyModule->is_enabled) {
                throw ModuleException::forDependencyNotEnabled($dependencyNamespace);
            }

            // Check version requirement if specified
            if ($requiredVersion !== null) {
                $this->validateDependencyVersion($dependencyNamespace, $dependencyModule->installed_version, $requiredVersion);
            }
        }

        if ($missingDependencies !== []) {
            throw ModuleException::forMissingDependencies($missingDependencies);
        }
    }

    /**
     * Validate that a dependency's version meets the requirement
     */
    private function validateDependencyVersion(string $namespace, ?string $installedVersion, array|string $requiredVersion): void
    {
        if ($installedVersion === null) {
            // Dependency is installed but no version recorded - allow it
            return;
        }

        if (is_string($requiredVersion)) {
            // String format: minimum version only
            if (version_compare($installedVersion, $requiredVersion, '<')) {
                throw ModuleException::forDependencyVersionTooOld($namespace, $installedVersion, $requiredVersion);
            }
        } elseif (is_array($requiredVersion)) {
            // Array format: [min, max] version range
            if (count($requiredVersion) !== 2) {
                throw ModuleException::forInvalidDependencyVersionFormat($namespace);
            }

            [$minVersion, $maxVersion] = $requiredVersion;

            if (version_compare($installedVersion, $minVersion, '<') || version_compare($installedVersion, $maxVersion, '>')) {
                throw ModuleException::forDependencyVersionOutOfRange($namespace, $installedVersion, $minVersion, $maxVersion);
            }
        } else {
            throw ModuleException::forInvalidDependencyVersionFormat($namespace);
        }
    }

    /**
     * Load a module instance
     */
    private function loadModuleInstance(Module $module): BaseModule
    {
        if (! $module->namespace) {
            throw ModuleException::forNoNamespaceFound($module->folder_name);
        }

        $moduleFilePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php';

        if (! file_exists($moduleFilePath)) {
            throw ModuleException::forModuleFileNotFound($module->folder_name);
        }

        require_once $moduleFilePath;

        $className = $module->namespace . '\\Module';

        if (! class_exists($className)) {
            throw ModuleException::forModuleClassNotFound($className, $module->folder_name);
        }

        $instance = new $className();

        if (! $instance instanceof BaseModule) {
            throw ModuleException::forModuleMustExtendBaseModule($className);
        }

        return $instance;
    }
}
