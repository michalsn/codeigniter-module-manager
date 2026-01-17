<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModuleAutoloadTest extends CLITestCase
{
    public function testRun(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:autoload');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Regenerating modules autoload file', $output);
        $this->assertStringContainsString('regenerated successfully', $output);
    }

    public function testRunShowsFilePath(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:autoload');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Location:', $output);
        $this->assertStringContainsString('modules_psr4.php', $output);
    }

    public function testRunAfterEnablingModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:scan');
        command('module:install posts');
        command('module:enable posts');

        command('module:autoload');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('regenerated successfully', $output);

        $autoloadPath = WRITEPATH . 'modules_psr4.php';
        $this->assertFileExists($autoloadPath);

        $content = file_get_contents($autoloadPath);
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', $content);
    }
}
