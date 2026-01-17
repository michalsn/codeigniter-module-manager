<?php

declare(strict_types=1);

namespace Tests;

use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;
use Michalsn\CodeIgniterModuleManager\Services\ModuleScanner;
use Tests\Support\Config\ModuleManager as ModuleManagerConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ModuleScannerTest extends TestCase
{
    private ModuleScanner $scanner;
    private ModuleManagerConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config  = new ModuleManagerConfig();
        $this->scanner = new ModuleScanner($this->config);
    }

    public function testScanForModulesReturnsArray(): void
    {
        $result = $this->scanner->scanForModules();

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('invalid', $result);
        $this->assertNotEmpty($result['valid']);
    }

    public function testScanForModulesFindsValidModule(): void
    {
        $result = $this->scanner->scanForModules();

        $this->assertNotEmpty($result['valid']);

        // Should find the 'posts' test module
        $found = false;

        foreach ($result['valid'] as $module) {
            if ($module['folder_name'] === 'posts') {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Posts module should be found during scan');
    }

    public function testScanModuleReturnsCompleteData(): void
    {
        $moduleData = $this->scanner->scanModule('posts');

        $this->assertIsArray($moduleData);
        $this->assertArrayHasKey('name', $moduleData);
        $this->assertArrayHasKey('description', $moduleData);
        $this->assertArrayHasKey('version', $moduleData);
        $this->assertArrayHasKey('author', $moduleData);
        $this->assertArrayHasKey('url', $moduleData);
        $this->assertArrayHasKey('folder_name', $moduleData);
        $this->assertArrayHasKey('namespace', $moduleData);

        $this->assertSame('Posts', $moduleData['name']);
        $this->assertSame('posts', $moduleData['folder_name']);
        $this->assertSame('Tests\Support\Modules\Posts', $moduleData['namespace']);
    }

    public function testScanModuleReturnsNullForNonexistentFolder(): void
    {
        $moduleData = $this->scanner->scanModule('nonexistent-module');

        $this->assertNull($moduleData);
    }

    public function testScanModuleThrowsExceptionForMissingSrcFolder(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage("Module must have 'src' folder");

        // Create a temporary module folder without src/
        $tempModulePath = $this->config->folderPath . '/temp-module';
        mkdir($tempModulePath, 0755, true);

        try {
            $this->scanner->scanModule('temp-module');
        } finally {
            rmdir($tempModulePath);
        }
    }

    public function testScanModuleThrowsExceptionForMissingModuleFile(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Module file \'Module.php\' not found');

        // Create a temporary module folder with src/ but no Module.php
        $tempModulePath = $this->config->folderPath . '/temp-module';
        $srcPath        = $tempModulePath . '/src';
        mkdir($srcPath, 0755, true);

        try {
            $this->scanner->scanModule('temp-module');
        } finally {
            rmdir($srcPath);
            rmdir($tempModulePath);
        }
    }

    public function testScanModuleThrowsExceptionForMissingNamespace(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('No namespace found in module file');

        // Create a temporary module with Module.php but no namespace
        $tempModulePath = $this->config->folderPath . '/temp-module';
        $srcPath        = $tempModulePath . '/src';
        mkdir($srcPath, 0755, true);

        $moduleFile = $srcPath . '/Module.php';
        file_put_contents($moduleFile, '<?php' . PHP_EOL . 'class Module {}');

        try {
            $this->scanner->scanModule('temp-module');
        } finally {
            unlink($moduleFile);
            rmdir($srcPath);
            rmdir($tempModulePath);
        }
    }

    public function testScanModuleThrowsExceptionForMissingClass(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Module class');
        $this->expectExceptionMessage('not found');

        // Create a temporary module with namespace but wrong class name
        $tempModulePath = $this->config->folderPath . '/temp-module-missing-class';
        $srcPath        = $tempModulePath . '/src';
        mkdir($srcPath, 0755, true);

        $moduleFile = $srcPath . '/Module.php';
        file_put_contents($moduleFile, '<?php' . PHP_EOL . 'namespace Tests\Support\TempModuleMissingClass;' . PHP_EOL . 'class WrongClassName {}');

        try {
            $this->scanner->scanModule('temp-module-missing-class');
        } finally {
            unlink($moduleFile);
            rmdir($srcPath);
            rmdir($tempModulePath);
        }
    }

    public function testScanModuleThrowsExceptionForInvalidBaseClass(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('must extend BaseModule');

        // Create a temporary module that doesn't extend BaseModule
        $tempModulePath = $this->config->folderPath . '/temp-module';
        $srcPath        = $tempModulePath . '/src';
        mkdir($srcPath, 0755, true);

        $moduleFile = $srcPath . '/Module.php';
        file_put_contents($moduleFile, '<?php' . PHP_EOL . 'namespace Tests\Support\TempModule;' . PHP_EOL . 'class Module {}');

        try {
            $this->scanner->scanModule('temp-module');
        } finally {
            unlink($moduleFile);
            rmdir($srcPath);
            rmdir($tempModulePath);
        }
    }

    public function testValidateModuleStructureReturnsEmptyArrayForValidModule(): void
    {
        $errors = $this->scanner->validateModuleStructure('posts');

        $this->assertEmpty($errors);
    }

    public function testValidateModuleStructureReturnsErrorsForNonexistentFolder(): void
    {
        $errors = $this->scanner->validateModuleStructure('nonexistent');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('does not exist', (string) $errors[0]);
    }

    public function testValidateModuleStructureReturnsErrorsForMissingSrc(): void
    {
        $tempModulePath = $this->config->folderPath . '/temp-module';
        mkdir($tempModulePath, 0755, true);

        try {
            $errors = $this->scanner->validateModuleStructure('temp-module');

            $this->assertNotEmpty($errors);
            $this->assertStringContainsString("must have 'src' folder", (string) $errors[0]);
        } finally {
            rmdir($tempModulePath);
        }
    }

    public function testValidateModuleStructureReturnsErrorsForMissingModuleFile(): void
    {
        $tempModulePath = $this->config->folderPath . '/temp-module';
        $srcPath        = $tempModulePath . '/src';
        mkdir($srcPath, 0755, true);

        try {
            $errors = $this->scanner->validateModuleStructure('temp-module');

            $this->assertNotEmpty($errors);
            $this->assertStringContainsString('Module.php', (string) $errors[0]);
            $this->assertStringContainsString('not found', (string) $errors[0]);
        } finally {
            rmdir($srcPath);
            rmdir($tempModulePath);
        }
    }

    public function testGetModuleNamespaceReturnsCorrectNamespace(): void
    {
        $namespace = $this->scanner->getModuleNamespace('posts');

        $this->assertSame('Tests\Support\Modules\Posts', $namespace);
    }

    public function testGetModuleNamespaceReturnsNullForNonexistentModule(): void
    {
        $namespace = $this->scanner->getModuleNamespace('nonexistent');

        $this->assertNull($namespace);
    }

    public function testScanForModulesTracksInvalidModules(): void
    {
        // Create an invalid module (missing src/ folder)
        $tempModulePath = $this->config->folderPath . '/invalid-module';
        mkdir($tempModulePath, 0755, true);

        try {
            $result = $this->scanner->scanForModules();

            // Invalid module should not be in valid results
            foreach ($result['valid'] as $module) {
                $this->assertNotSame('invalid-module', $module['folder_name']);
            }

            // Invalid module should be tracked in invalid array
            $this->assertArrayHasKey('invalid-module', $result['invalid']);
            $this->assertStringContainsString("must have 'src' folder", $result['invalid']['invalid-module']);
        } finally {
            rmdir($tempModulePath);
        }
    }

    public function testScanForModulesIgnoresDotDirectories(): void
    {
        $result = $this->scanner->scanForModules();

        // Should not include . or .. directories in valid modules
        foreach ($result['valid'] as $module) {
            $this->assertNotSame('.', $module['folder_name']);
            $this->assertNotSame('..', $module['folder_name']);
        }

        // Should not include . or .. directories in invalid modules
        $this->assertArrayNotHasKey('.', $result['invalid']);
        $this->assertArrayNotHasKey('..', $result['invalid']);
    }

    public function testScanForModulesReturnsEmptyArrayWhenModulePathDoesNotExist(): void
    {
        $config             = new ModuleManagerConfig();
        $config->folderPath = '/nonexistent/path';
        $scanner            = new ModuleScanner($config);

        $result = $scanner->scanForModules();

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('invalid', $result);
        $this->assertEmpty($result['valid']);
        $this->assertEmpty($result['invalid']);
    }
}
