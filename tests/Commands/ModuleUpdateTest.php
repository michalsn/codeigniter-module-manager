<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModuleUpdateTest extends CLITestCase
{
    public function testRunWithNoIdentifierAndNoFlag(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:update');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module identifier is required', $output);
        $this->assertStringContainsString('--all', $output);
    }

    public function testRunWithNonExistentModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:update nonexistent');
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
        command('module:update posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();
        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('must be installed', $output);
    }

    public function testRunWithNoUpdateAvailable(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();

        // Scan and install first
        command('module:scan');
        command('module:install posts');
        command('module:update posts');

        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();
        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('No update available', $output);
    }

    public function testRunUpdateAll(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();

        command('module:scan');
        command('module:update --all');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();
        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Checking for module updates', $output);
    }

    public function testRunUpdateAllWithNoModules(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:update --all');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('No modules require updates', $output);
    }
}
