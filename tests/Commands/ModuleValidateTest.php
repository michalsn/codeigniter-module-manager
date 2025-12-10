<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Filters\CITestStreamFilter;
use Tests\Support\CLITestCase;

/**
 * @internal
 */
final class ModuleValidateTest extends CLITestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;

    public function testRunWithNoIdentifier(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:validate');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module identifier is required', $output);
    }

    public function testRunWithNonExistentModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addErrorFilter();

        command('module:validate nonexistent');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeErrorFilter();

        $this->assertStringContainsString('Module not found', $output);
    }

    public function testRunWithValidModule(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        // First scan to register the test module
        command('module:scan');

        command('module:validate posts');
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertStringContainsString('Validating module', $output);
        $this->assertStringContainsString('is valid', $output);
    }
}
