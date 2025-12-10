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
final class ModuleUninstallTest extends CLITestCase
{
    public function testRunWithNoIdentifier(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:uninstall');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module identifier is required', $output);
    }

    public function testRunWithNonExistentModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:uninstall nonexistent');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module not found', $output);
    }

    public function testRunWithEnabledModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();

        // Scan, install, and enable first
        command('module:scan');
        command('module:install posts');
        command('module:enable posts');
        command('module:uninstall posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();
        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('must be disabled first', $output);
    }

    public function testRunSuccessfully(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        // Scan and install first (but don't enable)
        command('module:scan');
        command('module:install posts');
        command('module:uninstall posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Uninstalling module', $output);
        $this->assertStringContainsString('uninstalled successfully', $output);

        // Verify database state
        $moduleModel = new ModuleModel();
        /** @var Module|null $module */
        $module = $moduleModel->where('folder_name', 'posts')->first();

        $this->assertInstanceOf(Module::class, $module);
        $this->assertFalse($module->is_installed);
    }
}
