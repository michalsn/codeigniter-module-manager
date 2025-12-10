<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager;

/**
 * Base Update Manifest
 *
 * Maps module versions to their corresponding migrations.
 * Used for incremental updates when users skip versions.
 */
abstract class BaseUpdateManifest
{
    /**
     * Version-to-migrations mapping
     *
     * @var array<string, list<string>>
     */
    protected array $migrations = [];

    /**
     * Get migrations for a specific version
     *
     * @param string $version Semantic version (e.g., '1.0.0')
     *
     * @return list<string> Array of migration filenames (without .php extension)
     */
    public function getMigrationsForVersion(string $version): array
    {
        if (! isset($this->migrations[$version])) {
            return [];
        }

        return $this->migrations[$version];
    }

    /**
     * Get all migrations between two versions (exclusive of fromVersion, inclusive of toVersion)
     *
     * @param string $fromVersion Starting version (excluded)
     * @param string $toVersion   Ending version (included)
     *
     * @return list<string> Array of migration filenames
     */
    public function getMigrationsBetween(string $fromVersion, string $toVersion): array
    {
        $versions   = array_keys($this->migrations);
        $migrations = [];

        foreach ($versions as $version) {
            if (version_compare($version, $fromVersion, '>')
                && version_compare($version, $toVersion, '<=')) {
                $migrations = array_merge($migrations, $this->migrations[$version]);
            }
        }

        return $migrations;
    }

    /**
     * Get all defined versions in the manifest
     *
     * @return list<string> Array of version strings
     */
    public function getAllVersions(): array
    {
        return array_keys($this->migrations);
    }
}
