<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Services;

use Michalsn\CodeIgniterModuleManager\Config\ModuleManager as ModuleManagerConfig;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;

/**
 * Module Registry
 *
 * Centralized service for module discovery and lookup.
 * Provides caching to reduce database queries and file operations.
 */
class ModuleRegistry
{
    /**
     * Cache for module lookups (by folder name)
     *
     * @var array<string, Module|null>
     */
    private array $cacheByFolder = [];

    /**
     * Cache for module lookups (by namespace)
     *
     * @var array<string, Module|null>
     */
    private array $cacheByNamespace = [];

    /**
     * Cache for module lists
     *
     * @var array<string, array>
     */
    private array $cacheLists = [];

    public function __construct(private readonly ModuleManagerConfig $config, private readonly ModuleModel $moduleModel)
    {
    }

    /**
     * Get a module by identifier (folder name or namespace)
     *
     * @param string $identifier Module folder name or namespace
     */
    public function get(string $identifier): ?Module
    {
        // If identifier contains backslash, it's a namespace
        if (str_contains($identifier, '\\')) {
            return $this->getByNamespace($identifier);
        }

        // Otherwise, treat as folder name
        return $this->getByFolderName($identifier);
    }

    /**
     * Get a module by namespace
     *
     * @param string $namespace Module namespace (e.g., "Modules\Posts")
     */
    public function getByNamespace(string $namespace): ?Module
    {
        // Check cache first
        if (array_key_exists($namespace, $this->cacheByNamespace)) {
            return $this->cacheByNamespace[$namespace];
        }

        /** @var Module|null $module */
        $module = $this->moduleModel->where('namespace', $namespace)->first();

        // Cache result (even if null)
        $this->cacheByNamespace[$namespace] = $module;

        // Also cache by folder name if module exists
        if ($module !== null) {
            $this->cacheByFolder[$module->folder_name] = $module;
        }

        return $module;
    }

    /**
     * Get a module by folder name
     *
     * @param string $folderName Module folder name
     */
    public function getByFolderName(string $folderName): ?Module
    {
        // Check cache first
        if (array_key_exists($folderName, $this->cacheByFolder)) {
            return $this->cacheByFolder[$folderName];
        }

        // Query database
        $module = $this->moduleModel->findByFolderName($folderName);

        // Cache result (even if null)
        $this->cacheByFolder[$folderName] = $module;

        // Also cache by namespace if module exists and has namespace
        if ($module !== null && ! empty($module->namespace)) {
            $this->cacheByNamespace[$module->namespace] = $module;
        }

        return $module;
    }

    /**
     * Check if a module exists
     *
     * @param string $identifier Module folder name or namespace
     */
    public function has(string $identifier): bool
    {
        return $this->get($identifier) !== null;
    }

    /**
     * Get all modules
     *
     * @return list<Module>
     */
    public function all(): array
    {
        $cacheKey = 'all';

        if (isset($this->cacheLists[$cacheKey])) {
            return $this->cacheLists[$cacheKey];
        }

        $modules = $this->moduleModel->findAll();

        // Cache individual modules
        foreach ($modules as $module) {
            $this->cacheByFolder[$module->folder_name] = $module;
            if (! empty($module->namespace)) {
                $this->cacheByNamespace[$module->namespace] = $module;
            }
        }

        // Cache list
        $this->cacheLists[$cacheKey] = $modules;

        return $modules;
    }

    /**
     * Get all installed modules
     *
     * @return list<Module>
     */
    public function installed(): array
    {
        $cacheKey = 'installed';

        if (isset($this->cacheLists[$cacheKey])) {
            return $this->cacheLists[$cacheKey];
        }

        $modules = $this->moduleModel->getInstalled();

        // Cache individual modules
        foreach ($modules as $module) {
            $this->cacheByFolder[$module->folder_name] = $module;
            if (! empty($module->namespace)) {
                $this->cacheByNamespace[$module->namespace] = $module;
            }
        }

        // Cache list
        $this->cacheLists[$cacheKey] = $modules;

        return $modules;
    }

    /**
     * Get all enabled modules
     *
     * @return list<Module>
     */
    public function enabled(): array
    {
        $cacheKey = 'enabled';

        if (isset($this->cacheLists[$cacheKey])) {
            return $this->cacheLists[$cacheKey];
        }

        $modules = $this->moduleModel->getEnabled();

        // Cache individual modules
        foreach ($modules as $module) {
            $this->cacheByFolder[$module->folder_name] = $module;
            if (! empty($module->namespace)) {
                $this->cacheByNamespace[$module->namespace] = $module;
            }
        }

        // Cache list
        $this->cacheLists[$cacheKey] = $modules;

        return $modules;
    }

    /**
     * Get all disabled modules
     *
     * @return list<Module>
     */
    public function disabled(): array
    {
        $cacheKey = 'disabled';

        if (isset($this->cacheLists[$cacheKey])) {
            return $this->cacheLists[$cacheKey];
        }

        $modules = $this->moduleModel->getDisabled();

        // Cache individual modules
        foreach ($modules as $module) {
            $this->cacheByFolder[$module->folder_name] = $module;
            if (! empty($module->namespace)) {
                $this->cacheByNamespace[$module->namespace] = $module;
            }
        }

        // Cache list
        $this->cacheLists[$cacheKey] = $modules;

        return $modules;
    }

    /**
     * Get all modules with available updates
     *
     * @return list<Module>
     */
    public function updatable(): array
    {
        $cacheKey = 'updatable';

        if (isset($this->cacheLists[$cacheKey])) {
            return $this->cacheLists[$cacheKey];
        }

        $modules = $this->moduleModel->getUpdatable();

        // Cache individual modules
        foreach ($modules as $module) {
            $this->cacheByFolder[$module->folder_name] = $module;
            if (! empty($module->namespace)) {
                $this->cacheByNamespace[$module->namespace] = $module;
            }
        }

        // Cache list
        $this->cacheLists[$cacheKey] = $modules;

        return $modules;
    }

    /**
     * Get module's full path
     *
     * @param string $folderName Module folder name
     *
     * @return string Full path to module directory
     */
    public function getPath(string $folderName): string
    {
        return $this->config->folderPath . DIRECTORY_SEPARATOR . $folderName;
    }

    /**
     * Get modules directory path
     *
     * @return string Path to modules directory
     */
    public function getModulesPath(): string
    {
        return $this->config->folderPath;
    }

    /**
     * Clear all caches
     * Call this after database changes (install, uninstall, enable, disable)
     */
    public function refresh(): void
    {
        $this->cacheByFolder    = [];
        $this->cacheByNamespace = [];
        $this->cacheLists       = [];
    }

    /**
     * Invalidate cache for a specific module
     *
     * @param string $identifier Module folder name or namespace
     */
    public function invalidate(string $identifier): void
    {
        // Remove from folder cache
        if (isset($this->cacheByFolder[$identifier])) {
            $module = $this->cacheByFolder[$identifier];
            unset($this->cacheByFolder[$identifier], $this->cacheByNamespace[$module->namespace]);

            // Also remove from namespace cache
        }

        // Remove from namespace cache
        if (isset($this->cacheByNamespace[$identifier])) {
            $module = $this->cacheByNamespace[$identifier];
            unset($this->cacheByNamespace[$identifier], $this->cacheByFolder[$module->folder_name]);

            // Also remove from folder cache
        }

        // Clear list caches (they may contain the invalidated module)
        $this->cacheLists = [];
    }

    /**
     * Get cache statistics (for debugging)
     */
    public function getCacheStats(): array
    {
        return [
            'cached_by_folder'    => count($this->cacheByFolder),
            'cached_by_namespace' => count($this->cacheByNamespace),
            'cached_lists'        => array_keys($this->cacheLists),
        ];
    }
}
