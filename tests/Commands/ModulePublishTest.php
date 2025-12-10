<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModulePublishTest extends CLITestCase
{
    private string $configFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configFile = APPPATH . 'Config/ModuleManager.php';

        // Clean up any previously published files
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up published files
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
    }

    public function testRun(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:publish');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Config Published!', $output);
        $this->assertStringContainsString('app/Config/ModuleManager.php', $output);
    }

    public function testModifiesAutoload(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:publish');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        // Check if Autoload was already modified or newly modified
        $this->assertTrue(
            str_contains($output, 'Autoload Modified!') || str_contains($output, 'Autoload Already Modified!'),
        );
    }
}
