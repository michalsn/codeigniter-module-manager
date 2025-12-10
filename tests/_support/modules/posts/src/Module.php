<?php

declare(strict_types=1);

namespace Tests\Support\Modules\Posts;

use Michalsn\CodeIgniterModuleManager\BaseModule;

/**
 * Posts Module
 *
 * A sample module demonstrating the Module Manager functionality.
 * Provides basic blog post management capabilities.
 */
class Module extends BaseModule
{
    protected string $name        = 'Posts';
    protected string $description = 'A simple blog posts management module';
    protected string $version     = '1.0.0';
    protected string $author      = 'Module Manager Demo';
    protected ?string $url        = 'https://github.com/michalsn/codeigniter-module-manager';

    /**
     * Called after migrations run during installation
     * Use this for one-time setup tasks
     */
    public function onInstall(): void
    {
        log_message('info', 'Posts module installed - database tables created');
    }

    /**
     * Called before migrations rollback during uninstallation
     * Use this for cleanup tasks
     */
    public function onUninstall(): void
    {
        log_message('info', 'Posts module being uninstalled - cleaning up');
    }

    /**
     * Called when module is enabled
     */
    public function onEnable(): void
    {
        log_message('info', 'Posts module enabled - routes and controllers now available');
    }

    /**
     * Called when module is disabled
     */
    public function onDisable(): void
    {
        log_message('info', 'Posts module disabled - functionality deactivated');
    }

    /**
     * Called when module is updated
     */
    public function onUpdate(string $fromVersion, string $toVersion): void
    {
        log_message('info', "Posts module updated from {$fromVersion} to {$toVersion}");
    }

    /**
     * Get required CodeIgniter version
     */
    public function getRequiredCIVersion(): ?string
    {
        return '4.6.0';
    }

    /**
     * Get module dependencies
     * Example: return ['users', 'comments'];
     */
    public function getDependencies(): array
    {
        return [];
    }
}
