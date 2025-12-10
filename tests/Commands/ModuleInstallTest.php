<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModuleInstallTest extends CLITestCase
{
    public function testRunWithNoIdentifier(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:install');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module identifier is required', $output);
    }

    public function testRunWithNonExistentModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:install nonexistent');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module not found', $output);
    }

    public function testRunSuccessfully(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        // Scan first to register the module
        command('module:scan');
        command('module:install posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Installing module', $output);
        $this->assertStringContainsString('installed successfully', $output);

        // Verify database state
        $moduleModel = new ModuleModel();
        /** @var Module|null $module */
        $module = $moduleModel->where('folder_name', 'posts')->first();

        $this->assertInstanceOf(Module::class, $module);
        $this->assertTrue($module->is_installed);
    }

    public function testRunWithAlreadyInstalledModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();

        // Scan and install first
        command('module:scan');
        command('module:install posts');
        command('module:install posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();
        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('already installed', $output);
    }
}
