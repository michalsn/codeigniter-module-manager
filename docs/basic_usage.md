# Basic Usage

Working with modules involves understanding their structure and how to move them through their lifecycle. This guide will walk you through creating a simple module and managing it from start to finish.

## Creating Your First Module

A module starts with a specific directory structure. At minimum, you need a Module class that extends `BaseModule` and provides metadata about your module. Let's create a simple blog module.

Start by creating the directory structure in your modules folder:

```
modules/blog/src/
└── Module.php
```

!!! note

    The ``src`` folder is required and must be present for modules to be detected.

The Module.php file defines your module's identity and configuration:

```php
<?php

namespace Modules\Blog;

use Michalsn\CodeIgniterModuleManager\BaseModule;

class Module extends BaseModule
{
    protected string $name = 'Blog';
    protected string $description = 'A simple blog module for managing posts';
    protected string $version = '1.0.0';
    protected string $author = 'Your Name';
    protected ?string $url = 'https://example.com';
}
```

These properties are required. The name identifies your module to users, the description explains what it does, the version helps track updates, and the author information gives credit and contact details.

## Discovering Modules

Once your module directory exists, the Module Manager needs to discover it. Run the scan command to register your module with the system:

```console
php spark module:scan
```

This command reads through your modules directory, finds all valid modules, and adds them to the database. If you already had modules registered and you've deleted some directories, the scan command will automatically clean up those orphaned records.

To verify your module was discovered, list all available modules:

```console
php spark module:list
```

You'll see your blog module listed with its version and installation status. At this point, the module is known to the system but not yet installed or enabled.

## Adding Database Migrations

Most modules need database tables to store their data. Migrations handle creating and dropping these tables in a reversible way. Create a migrations directory in your module:

```
modules/blog/src/
├── Module.php
└── Database/
    └── Migrations/
        └── 2025-01-15-100000_CreateBlogTables.php
```

The migration file follows CodeIgniter's standard migration format:

```php
<?php

namespace Modules\Blog\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBlogTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'content' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('blog_posts');
    }

    public function down()
    {
        $this->forge->dropTable('blog_posts', true);
    }
}
```

The up method creates your tables, while the down method removes them. This reversibility is crucial for clean uninstallation.

## Installing the Module

Installation runs your migrations and creates the database schema. During this process, the module's namespace is temporarily registered so migrations can access any classes they need:

```console
php spark module:install Modules\\Blog
```

The command executes the up method in your migration, creates the blog_posts table, and marks the module as installed in the database. However, the module is still not active in your application. Its routes won't work and its classes are not autoloaded yet.

## Adding Routes and Controllers

Before enabling the module, let's add some functionality. Create a controller and routes file:

```
modules/blog/src/
├── Module.php
├── Config/
│   └── Routes.php
├── Controllers/
│   └── BlogController.php
└── Database/
    └── Migrations/
        └── 2025-01-15-100000_CreateBlogTables.php
```

The Routes.php file defines your module's URL patterns:

```php
<?php

$routes->group('blog', ['namespace' => 'Modules\Blog\Controllers'], function ($routes) {
    $routes->get('/', 'BlogController::index');
    $routes->get('(:segment)', 'BlogController::view/$1');
});
```

And the controller handles requests:

```php
<?php

namespace Modules\Blog\Controllers;

use App\Controllers\BaseController;

class BlogController extends BaseController
{
    public function index()
    {
        d('Blog - index');
    }

    public function view($slug)
    {
        d('Blog - ' . $slug);
    }
}
```

## Enabling the Module

Enabling makes your module fully functional. The system adds the module's namespace to the PSR-4 autoloader cache and marks it as enabled:

```console
php spark module:enable Modules\\Blog
```

Now your module is active. Visit `/blog` in your browser and your routes will work. The BlogController class is autoloaded and can handle requests.

## Lifecycle Hooks

Modules can respond to lifecycle events by overriding hook methods in the Module class. These hooks let you perform setup or cleanup tasks at specific points:

```php
<?php

namespace Modules\Blog;

use Michalsn\CodeIgniterModuleManager\BaseModule;

class Module extends BaseModule
{
    protected string $name = 'Blog';
    protected string $description = 'A simple blog module';
    protected string $version = '1.0.0';
    protected string $author = 'Your Name';

    public function onInstall(): void
    {
        // Called after migrations run
        // Create config files, seed data, etc.
        log_message('info', 'Blog module installed');
    }

    public function onEnable(): void
    {
        // Called when module is enabled
        // Publish assets, register event listeners, etc.
        log_message('info', 'Blog module enabled');
    }

    public function onDisable(): void
    {
        // Called when module is disabled
        // Clean up temporary data, revoke access, etc.
        log_message('info', 'Blog module disabled');
    }

    public function onUninstall(): void
    {
        // Called before migrations are rolled back
        // Remove uploaded files, clear caches, etc.
        log_message('info', 'Blog module uninstalled');
    }
}
```

## Disabling and Uninstalling

When you no longer need a module's functionality, disable it:

```console
php spark module:disable Modules\\Blog
```

This removes the module from the autoloader cache and deactivates its routes, but preserves all data in the database.

To completely remove a module, including its database tables, first disable it and then uninstall:

```console
php spark module:disable Modules\\Blog
php spark module:uninstall Modules\\Blog
```

The uninstall command calls your migration's down method, drops the blog_posts table, and marks the module as not installed. The module remains in your modules directory and can be installed again if needed.

## Module Dependencies

Modules can depend on other modules or require specific CodeIgniter versions. Declare these requirements in your Module class:

```php
// Minimum version only
public function getRequiredCIVersion(): string|array|null
{
    return '4.6.0';
}

// Or specify a version range
public function getRequiredCIVersion(): string|array|null
{
    return ['4.6.0', '4.9.9'];  // Min and max versions
}

// Simple dependencies without version requirements
public function getDependencies(): array
{
    return ['Modules\\Users', 'Modules\\Comments'];
}

// Or specify version requirements for dependencies
public function getDependencies(): array
{
    return [
        'Modules\\Users' => '2.0.0',               // Minimum version
        'Modules\\Comments' => ['1.0.0', '2.0.0'], // Version range
        'Modules\\Tags' => null,                   // Any version
    ];
}
```

Both CodeIgniter version and dependency versions can be specified as a string for minimum version only, or as an array with exactly two elements for a version range. This is useful when your module is not compatible with newer versions due to breaking changes.

Dependencies must be specified using full namespaces. You can provide a simple list of namespaces if you don't care about versions, or use an associative array to specify version requirements for each dependency.

The Module Manager validates these requirements before installation. It checks that the current CodeIgniter version meets the requirements, that all dependent modules are both installed and enabled, and that their versions satisfy any specified constraints. If requirements are not met, installation will be blocked with a clear error message indicating what needs to be updated.

## Checking Module Status

You can inspect a module's current state and available information with the info command:

```console
php spark module:info Modules\\Blog
```

This shows the module's name, description, version, installation status, enabled status, and whether any updates are available.

## Working Programmatically

You can also interact with modules through code in your application:

```php
$moduleManager = service('moduleManager');

// Check if a module is enabled
if ($moduleManager->isModuleEnabled('Modules\\Blog')) {
    // Module is active
}

// Get module statistics
$stats = $moduleManager->getModuleStats();
// Returns array with counts of total, installed, enabled modules
```

This programmatic access lets you build dynamic interfaces or automation around your module ecosystem.
