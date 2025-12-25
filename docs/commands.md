# Commands

The Module Manager provides a comprehensive set of commands for managing your modules through the CodeIgniter CLI. These commands handle everything from discovery to cleanup, giving you complete control over your module ecosystem.

## module:scan

Discovers and registers modules in your modules directory. This command reads each module's Module.php file, extracts metadata, and stores it in the database.

```console
php spark module:scan
```

The scan command is intelligent about cleanup. If you've deleted module directories since the last scan, those orphaned database records are automatically removed. This keeps your module registry synchronized with the actual files on disk.

Run this command whenever you add new modules to your project or after pulling changes from version control that include module additions or deletions.

## module:list

Displays a table of all registered modules with their current status. This gives you a quick overview of your entire module ecosystem at a glance.

```console
php spark module:list
```

The output shows each module's name, version, installation status, enabled status, and whether updates are available. Use this command to verify that modules are in the expected state after performing lifecycle operations.

## module:info

Shows detailed information about a specific module. This includes all metadata from the Module class plus current installation and enablement status.

```console
php spark module:info <module_namespace>
```
!!! important

    Replace `<module_namespace>` with either the module's folder name (like `blog`) or its namespace (like `Modules\Blog`). For convenience, the command supports both formats. This convention is used across all commands.

The detailed output includes the module's name, description, version, author, URL, folder location, namespace, whether it's installed and enabled, the installed version if different from the current version, and whether updates are available.

## module:validate

Checks that a module's directory structure and files meet the Module Manager's requirements. This is useful for debugging why a module isn't being recognized or is failing to install.

```console
php spark module:validate <module_namespace>
```

The validation checks for the presence of the Module.php file, verifies that the Module class exists and extends BaseModule, confirms all required properties are set, and validates that the namespace matches expectations.

Use this command when developing modules or troubleshooting issues with third-party modules.

## module:install

Runs a module's database migrations to create its required tables and schema. The module must be scanned and registered before it can be installed.

```console
php spark module:install <module_namespace>
```

During installation, the module's namespace is temporarily registered with the autoloader. This allows migrations to reference any classes they need from the module. Once migrations complete, this temporary registration is removed.

The command marks the module as installed in the database and records which migrations have been run. However, the module is not yet active. Its routes won't work and its classes are not persistently autoloaded.

You must install a module before you can enable it. The system enforces this ordering to ensure the database schema exists before the module becomes functional.

## module:enable

Activates a module by adding its namespace to the PSR-4 autoloader configuration. This makes the module fully functional in your application.

```console
php spark module:enable <module_namespace>
```

The command updates the `writable/modules_psr4.php` cache file to include the module's namespace mapping. This file is loaded during application bootstrap, making the module's classes available for autoloading.

Routes defined in the module's Config/Routes.php file become active. Controllers can handle requests, models can access data, and all module functionality is available.

The module must be installed before you can enable it. If you try to enable a module that hasn't been installed, the command will reject the operation.

After enabling modules in production environments with opcode caching, you may need to clear the opcode cache or restart your web server to ensure the new autoloader configuration takes effect immediately.

## module:disable

Deactivates a module by removing its namespace from the autoloader configuration. This turns off the module's functionality without affecting its data.

```console
php spark module:disable <module_namespace>
```

The command updates the autoloader cache file to remove the module's namespace. Routes become unavailable, controllers cannot handle requests, and the module's classes are no longer autoloaded.

All data in the module's database tables remains intact. Disabling is a reversible operation that you can use to temporarily turn off modules without losing any information.

You must disable a module before you can uninstall it. This ensures that no active code is trying to use the database tables when they're being dropped.

## module:uninstall

Removes a module's database tables by rolling back its migrations. This performs a complete cleanup of the module's data.

```console
php spark module:uninstall <module_namespace>
```

The command first checks that the module is disabled. If the module is still enabled, the uninstall operation is rejected to prevent removing tables that active code might be using.

During uninstallation, the module's namespace is temporarily registered with the autoloader so migrations can access any classes they need for cleanup. The command then runs the down method of each migration in reverse order, dropping tables and removing schema elements.

After migrations complete, the temporary namespace registration is removed and the module is marked as not installed in the database. The module's directory remains on disk, allowing you to install it again if needed.

## module:update

Updates a module to its latest version by running new migrations and executing update logic. The module must be installed for updates to work.

```console
php spark module:update <module_namespace>
```

To update all modules at once:

```console
php spark module:update --all
```

The update process depends on whether the module includes an UpdateManifest file. Without a manifest, the system runs all pending migrations and calls the onUpdate hook once with the old and new version numbers.

With an UpdateManifest, the system processes versions incrementally. For each version between the currently installed version and the latest version, it runs that version's specific migrations and calls onUpdate with the appropriate version numbers. This allows for complex data transformations that depend on processing versions in order.

The UpdateManifest file lives in your module's root directory alongside Module.php:

```php
<?php

namespace Modules\Blog;

use Michalsn\CodeIgniterModuleManager\BaseUpdateManifest;

class UpdateManifest extends BaseUpdateManifest
{
    protected array $migrations = [
        '1.0.0' => ['2025-01-15-100000_CreateBlogTables'],
        '1.1.0' => ['2025-02-10-100000_AddCommentsTable'],
        '1.2.0' => [],  // No migrations, just logic updates
        '2.0.0' => [
            '2025-03-15-100000_RefactorSchema',
            '2025-03-15-101000_MigrateData',
        ],
    ];
}
```

This manifest ensures that users updating from any older version will have all intermediate migrations run in the correct order, even if they skipped some versions.
