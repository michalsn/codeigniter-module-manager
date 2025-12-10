<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager;

use Exception;

abstract class BaseModule
{
    protected string $name;
    protected string $description;
    protected string $version;
    protected string $author;
    protected ?string $url = null;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // Ensure child classes have the required fields
        if (! isset($this->name) || ($this->name === '' || $this->name === '0') || (! isset($this->description) || ($this->description === '' || $this->description === '0')) || (! isset($this->version) || ($this->version === '' || $this->version === '0')) || (! isset($this->author) || ($this->author === '' || $this->author === '0'))) {
            throw new Exception('Required module properties not properly defined.');
        }
    }

    public function getInfo(): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'version'     => $this->version,
            'author'      => $this->author,
            'url'         => $this->url,
        ];
    }

    // Optional: Getter methods for individual properties
    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Called when module is installed (after migrations run)
     * Override this method to perform one-time installation tasks
     * Examples: create config files, seed initial data, setup external resources
     */
    public function onInstall(): void
    {
        // Override in child classes if needed
    }

    /**
     * Called when module is uninstalled (before migrations are rolled back)
     * Override this method to perform cleanup tasks
     * Examples: remove uploaded files, clear caches, cleanup external resources
     */
    public function onUninstall(): void
    {
        // Override in child classes if needed
    }

    /**
     * Called when module is enabled
     * Override this method to perform setup tasks
     */
    public function onEnable(): void
    {
        // Override in child classes if needed
    }

    /**
     * Called when module is disabled
     * Override this method to perform cleanup tasks
     */
    public function onDisable(): void
    {
        // Override in child classes if needed
    }

    /**
     * Called when module is updated from one version to another
     * Override this method to perform update/migration tasks
     *
     * @param string $fromVersion The previous version
     * @param string $toVersion   The new version
     */
    public function onUpdate(string $fromVersion, string $toVersion): void
    {
        // Override in child classes if needed
    }

    /**
     * Get required CodeIgniter version
     * Override this method to specify minimum CI version required
     *
     * @return array{0: string, 1: string}|string|null String for minimum version only (e.g., '4.4.0'),
     *                                                 Array [min, max] for version range (e.g., ['4.4.0', '4.9.9']),
     *                                                 or null if no requirement
     */
    public function getRequiredCIVersion(): array|string|null
    {
        return null;
    }

    /**
     * Get module dependencies
     * Override this method to specify other modules this module depends on
     *
     * @return array<string, array{0: string, 1: string}|string|null>|list<string> List of namespaces ['Modules\\Users', 'Modules\\Posts'],
     *                                                                             or associative array with versions ['Modules\\Users' => '1.0.0', 'Modules\\Posts' => ['1.0.0', '2.0.0']]
     *                                                                             Version can be: string (minimum), array [min, max] (range), or null (any version)
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * Whether this module bundles its own dependencies
     * Override this method to return true if the module includes
     * a vendor/ folder with scoped dependencies
     */
    public function hasBundledDependencies(): bool
    {
        return false;
    }
}
