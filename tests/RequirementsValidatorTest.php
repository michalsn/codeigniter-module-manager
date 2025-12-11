<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\DatabaseTestTrait;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;
use Michalsn\CodeIgniterModuleManager\Services\ModuleRegistry;
use Michalsn\CodeIgniterModuleManager\Services\RequirementsValidator;
use ReflectionClass;
use ReflectionMethod;
use Tests\Support\Config\ModuleManager as ModuleManagerConfig;
use Tests\Support\TestCase;

/**
 * Tests for module version requirements validation
 *
 * @internal
 */
final class RequirementsValidatorTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    private ModuleModel $moduleModel;
    private RequirementsValidator $requirementsValidator;
    private ReflectionMethod $validateRequirementsMethod;
    private ReflectionMethod $validateDependencyVersionMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $config                      = new ModuleManagerConfig();
        $this->moduleModel           = new ModuleModel();
        $registry                    = new ModuleRegistry($config, $this->moduleModel);
        $this->requirementsValidator = new RequirementsValidator($config, $registry);

        // Make private methods accessible for testing
        $reflection                            = new ReflectionClass($this->requirementsValidator);
        $this->validateRequirementsMethod      = $reflection->getMethod('validate');
        $this->validateDependencyVersionMethod = $reflection->getMethod('validateDependencyVersion');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Cleanup
        $existing = null;

        foreach (glob(SUPPORTPATH . 'modules' . DIRECTORY_SEPARATOR . 'test-module*', GLOB_ONLYDIR) as $dir) {
            $existing = $dir;
            break;
        }

        if ($existing !== null && is_dir($existing)) {
            $moduleFile = $existing . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php';
            if (is_file($moduleFile)) {
                @unlink($moduleFile);
            }

            $srcDir = $existing . DIRECTORY_SEPARATOR . 'src';
            if (is_dir($srcDir)) {
                @rmdir($srcDir);
            }

            @rmdir($existing);
        }
    }

    public function testCodeIgniterVersionRequirementMet(): void
    {
        $module = $this->createMockModule('test-module1', 'TestModule1');

        $this->expectNotToPerformAssertions();

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testCodeIgniterVersionRequirementNotMet(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Module requires CodeIgniter 99.0.0 or newer');

        $module = $this->createMockModule('test-module2', 'TestModule2', ['ciVersion' => '99.0.0']);

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testCodeIgniterVersionRangeRequirementMet(): void
    {
        $minVersion = '4.0.0'; // Well below current
        $maxVersion = '99.0.0'; // Well above current

        $module = $this->createMockModule('test-module3', 'TestModule3', ['ciVersion' => [$minVersion, $maxVersion]]);

        $this->expectNotToPerformAssertions();

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testCodeIgniterVersionBelowMinimumRange(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Module requires CodeIgniter between 99.0.0 and 99.9.9');

        $module = $this->createMockModule('test-module4', 'TestModule4', ['ciVersion' => ['99.0.0', '99.9.9']]);

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testCodeIgniterVersionAboveMaximumRange(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Module requires CodeIgniter between 1.0.0 and 2.0.0');

        $module = $this->createMockModule('test-module5', 'TestModule5', ['ciVersion' => ['1.0.0', '2.0.0']]);

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testCodeIgniterVersionRangeInvalidFormat(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Invalid version range format');

        $module = $this->createMockModule('test-module6', 'TestModule6', ['ciVersion' => ['1.0.0']]);

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    // ========================================================================
    // Dependency Version Tests (Direct method testing)
    // ========================================================================

    public function testDependencyVersionMinimumMet(): void
    {
        $this->expectNotToPerformAssertions();
        // Should not throw when installed version >= required minimum
        $this->validateDependencyVersionMethod->invoke(
            $this->requirementsValidator,
            'Modules\\TestDependency',
            '2.0.0',
            '1.0.0',
        );
    }

    public function testDependencyVersionMinimumNotMet(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('version 1.0.0 is too old (requires 2.0.0 or newer)');

        $this->validateDependencyVersionMethod->invoke(
            $this->requirementsValidator,
            'Modules\\TestDependency',
            '1.0.0',
            '2.0.0',
        );
    }

    public function testDependencyVersionRangeMet(): void
    {
        $this->expectNotToPerformAssertions();
        // Version 1.5.0 is within range [1.0.0, 2.0.0]
        $this->validateDependencyVersionMethod->invoke(
            $this->requirementsValidator,
            'Modules\\TestDependency',
            '1.5.0',
            ['1.0.0', '2.0.0'],
        );
    }

    public function testDependencyVersionBelowRange(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('version 0.9.0 is outside required range 1.0.0 to 2.0.0');

        $this->validateDependencyVersionMethod->invoke(
            $this->requirementsValidator,
            'Modules\\TestDependency',
            '0.9.0',
            ['1.0.0', '2.0.0'],
        );
    }

    public function testDependencyVersionAboveRange(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('version 3.0.0 is outside required range 1.0.0 to 2.0.0');

        $this->validateDependencyVersionMethod->invoke(
            $this->requirementsValidator,
            'Modules\\TestDependency',
            '3.0.0',
            ['1.0.0', '2.0.0'],
        );
    }

    public function testDependencyVersionInvalidFormat(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Invalid version format for dependency');

        $this->validateDependencyVersionMethod->invoke(
            $this->requirementsValidator,
            'Modules\\TestDependency',
            '1.0.0',
            ['1.0.0'], // Invalid: only one element
        );
    }

    public function testDependencyVersionNullInstalledAllowed(): void
    {
        $this->expectNotToPerformAssertions();
        // Should not throw when installed version is null (module has no version info)
        $this->validateDependencyVersionMethod->invoke(
            $this->requirementsValidator,
            'Modules\\TestDependency',
            null,
            '1.0.0',
        );
    }

    public function testDependencyInstalledAndEnabledWithoutVersionCheck(): void
    {
        // Create a dependency module that's installed and enabled
        $this->moduleModel->insert([
            'folder_name'       => 'dep-module',
            'namespace'         => 'Modules\\DepModule',
            'name'              => 'DepModule',
            'description'       => 'Dependency module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        $module = $this->createMockModule('test-module9', 'TestModule9', [
            'dependencies' => ['Modules\\DepModule'],
        ]);

        $this->expectNotToPerformAssertions();

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testDependencyWithVersionRequirementMet(): void
    {
        // Create a dependency module with version 2.0.0
        $this->moduleModel->insert([
            'folder_name'       => 'dep-module',
            'namespace'         => 'Modules\\DepModule',
            'name'              => 'DepModule',
            'description'       => 'Dependency module',
            'version'           => '2.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '2.0.0',
        ]);

        $module = $this->createMockModule('test-module10', 'TestModule10', [
            'dependencies' => ['Modules\\DepModule' => '1.0.0'], // Requires 1.0.0+, has 2.0.0
        ]);

        $this->expectNotToPerformAssertions();

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testDependencyWithVersionRequirementNotMet(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('version 1.0.0 is too old (requires 2.0.0 or newer)');

        // Create a dependency module with version 1.0.0
        $this->moduleModel->insert([
            'folder_name'       => 'dep-module',
            'namespace'         => 'Modules\\DepModule',
            'name'              => 'DepModule',
            'description'       => 'Dependency module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        $module = $this->createMockModule('test-module11', 'TestModule11', [
            'dependencies' => ['Modules\\DepModule' => '2.0.0'], // Requires 2.0.0+, has 1.0.0
        ]);

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    public function testDependencyWithVersionRangeMet(): void
    {
        // Create a dependency module with version 1.5.0
        $this->moduleModel->insert([
            'folder_name'       => 'dep-module',
            'namespace'         => 'Modules\\DepModule',
            'name'              => 'DepModule',
            'description'       => 'Dependency module',
            'version'           => '1.5.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.5.0',
        ]);

        $module = $this->createMockModule('test-module12', 'TestModule12', [
            'dependencies' => ['Modules\\DepModule' => ['1.0.0', '2.0.0']], // Range 1.0.0-2.0.0, has 1.5.0
        ]);

        $this->expectNotToPerformAssertions();

        $this->validateRequirementsMethod->invoke($this->requirementsValidator, $module);
    }

    // Helper Methods

    /**
     * Create a mock module for testing
     *
     * @param array<string, mixed> $config
     */
    private function createMockModule(string $folderName, string $namespace, array $config = []): ?Module
    {
        // Create module directory structure
        $modulePath = SUPPORTPATH . 'modules' . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . 'src';
        if (! is_dir($modulePath)) {
            mkdir($modulePath, 0755, true);
        }

        // Create Module.php
        $this->createMockModuleFile($modulePath, $namespace, $config);

        // Insert module into database
        $moduleId = $this->moduleModel->insert([
            'folder_name' => $folderName,
            'namespace'   => 'Tests\\Support\\Modules\\' . $namespace,
            'name'        => $namespace,
            'description' => 'Test module',
            'version'     => $config['version'] ?? '1.0.0',
            'author'      => 'Test',
        ]);

        return $this->moduleModel->find($moduleId);
    }

    /**
     * Create Module.php file with specific configuration
     *
     * @param array<string, mixed> $config
     */
    private function createMockModuleFile(string $path, string $namespace, array $config): void
    {
        $ciVersion    = $config['ciVersion'] ?? null;
        $dependencies = $config['dependencies'] ?? [];

        // Format CI version requirement
        $ciVersionCode = 'null';
        if ($ciVersion !== null) {
            $ciVersionCode = is_array($ciVersion) ? "['" . implode("', '", $ciVersion) . "']" : "'{$ciVersion}'";
        }

        // Format dependencies
        $dependenciesCode = $this->formatDependenciesCode($dependencies);

        $content = <<<PHP
            <?php

            namespace Tests\\Support\\Modules\\{$namespace};

            use Michalsn\\CodeIgniterModuleManager\\BaseModule;

            class Module extends BaseModule
            {
                protected string \$name = '{$namespace}';
                protected string \$description = 'Test module for version requirements';
                protected string \$version = '1.0.0';
                protected string \$author = 'Test';

                public function getRequiredCIVersion(): array|string|null
                {
                    return {$ciVersionCode};
                }

                public function getDependencies(): array
                {
                    return {$dependenciesCode};
                }
            }
            PHP;

        file_put_contents($path . DIRECTORY_SEPARATOR . 'Module.php', $content);
    }

    /**
     * Format dependencies array as PHP code
     *
     * @param array<int|string, array|string|null> $dependencies
     */
    private function formatDependenciesCode(array $dependencies): string
    {
        if ($dependencies === []) {
            return '[]';
        }

        $lines = ['['];

        foreach ($dependencies as $key => $value) {
            if (is_int($key)) {
                // Simple list format
                $lines[] = "            '{$value}',";
            } elseif ($value === null) {
                // Associative format with version
                $lines[] = "            '{$key}' => null,";
            } elseif (is_array($value)) {
                $lines[] = "            '{$key}' => ['" . implode("', '", $value) . "'],";
            } else {
                $lines[] = "            '{$key}' => '{$value}',";
            }
        }

        $lines[] = '        ]';

        return implode("\n", $lines);
    }
}
