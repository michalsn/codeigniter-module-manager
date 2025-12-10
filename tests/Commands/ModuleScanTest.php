<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModuleScanTest extends CLITestCase
{
    public function testRun(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:scan');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Scanning for modules', $output);
        $this->assertStringContainsString('modules synced successfully', $output);
    }

    public function testRunFindsTestModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        command('module:scan');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('posts: OK', $output);
    }
}
