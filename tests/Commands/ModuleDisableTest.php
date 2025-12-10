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
final class ModuleDisableTest extends CLITestCase
{
    public function testRunWithNoIdentifier(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        $this->assertNotFalse(command('module:disable'));
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module identifier is required', $output);
    }

    public function testRunWithNonExistentModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        $this->assertNotFalse(command('module:disable nonexistent'));
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module not found', $output);
    }

    public function testRunSuccessfully(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        // Scan, install, and enable first
        command('module:scan');
        command('module:install posts');
        command('module:enable posts');
        command('module:disable posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Disabling module', $output);
        $this->assertStringContainsString('disabled successfully', $output);

        // Verify database state
        $moduleModel = new ModuleModel();
        /** @var Module|null $module */
        $module = $moduleModel->where('folder_name', 'posts')->first();

        $this->assertInstanceOf(Module::class, $module);
        $this->assertFalse($module->is_enabled);
    }
}
