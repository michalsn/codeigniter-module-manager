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
final class ModuleEnableTest extends CLITestCase
{
    public function testRunWithNoIdentifier(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:enable');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module identifier is required', $output);
    }

    public function testRunWithNonExistentModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();
        command('module:enable nonexistent');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module not found', $output);
    }

    public function testRunWithNotInstalledModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();

        // Scan but don't install
        command('module:scan');
        command('module:enable posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();
        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('cannot be enabled', $output);
    }

    public function testRunSuccessfully(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        // Scan and install first
        command('module:scan');
        command('module:install posts');
        command('module:enable posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Enabling module', $output);
        $this->assertStringContainsString('enabled successfully', $output);

        // Verify database state
        $moduleModel = new ModuleModel();
        /** @var Module|null $module */
        $module = $moduleModel->where('folder_name', 'posts')->first();

        $this->assertInstanceOf(Module::class, $module);
        $this->assertTrue($module->is_enabled);
    }
}
