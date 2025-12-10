<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModuleInfoTest extends CLITestCase
{
    public function testRunWithNoIdentifier(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:info');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module identifier is required', $output);
    }

    public function testRunWithNonExistentModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:info nonexistent');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module not found', $output);
        $this->assertStringContainsString('php spark module:scan', $output);
    }

    public function testRunWithExistingModule(): void
    {
        $moduleModel = new ModuleModel();
        $moduleModel->insert([
            'name'              => 'Posts',
            'description'       => 'Test module description',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'version'           => '1.0.0',
            'author'            => 'Test Author',
            'url'               => 'https://example.com',
            'folder_name'       => 'posts',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:info posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Module Information: Posts', $output);
        $this->assertStringContainsString('Test module description', $output);
        $this->assertStringContainsString('Tests\Support\Modules\Posts', $output);
        $this->assertStringContainsString('1.0.0', $output);
        $this->assertStringContainsString('Test Author', $output);
        $this->assertStringContainsString('https://example.com', $output);
    }
}
