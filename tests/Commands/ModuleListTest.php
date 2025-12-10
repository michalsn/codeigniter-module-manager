<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModuleListTest extends CLITestCase
{
    public function testRunWithNoModules(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:list');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('No modules found', $output);
        $this->assertStringContainsString('php spark module:scan', $output);
    }

    public function testRunWithModules(): void
    {
        $moduleModel = new ModuleModel();
        $moduleModel->insert([
            'name'              => 'Posts',
            'description'       => 'Test module',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'version'           => '1.0.0',
            'author'            => 'Test Author',
            'folder_name'       => 'posts',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:list');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Posts', $output);
        $this->assertStringContainsString('Tests\Support\Modules\Posts', $output);
        $this->assertStringContainsString('1.0.0', $output);
        $this->assertStringContainsString('Yes', $output); // Installed & Enabled
    }
}
