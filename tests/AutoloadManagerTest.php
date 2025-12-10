<?php

declare(strict_types=1);

namespace Tests;

use Michalsn\CodeIgniterModuleManager\Services\AutoloadManager;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AutoloadManagerTest extends TestCase
{
    private AutoloadManager $autoloadManager;
    private string $autoloadFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->autoloadManager = new AutoloadManager();
        $this->autoloadFile    = WRITEPATH . 'modules_psr4.php';

        // Clean up autoload file if exists
        if (file_exists($this->autoloadFile)) {
            unlink($this->autoloadFile);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up autoload file
        if (file_exists($this->autoloadFile)) {
            unlink($this->autoloadFile);
        }
    }

    public function testRegenerateAutoloadFileCreatesFile(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $this->assertFileExists($this->autoloadFile);
    }

    public function testRegenerateAutoloadFileContainsPSR4Mappings(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', (string) $content);
        $this->assertStringContainsString('return [', (string) $content);
    }

    public function testRegenerateAutoloadFileWithMultipleModules(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
            [
                'namespace'        => 'Tests\Support\Modules\Comments',
                'path'             => TESTPATH . '_support/modules/comments/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', (string) $content);
        // Path must be valid
        $this->assertStringNotContainsString('Tests\\\\Support\\\\Modules\\\\Comments', (string) $content);
    }

    public function testRegenerateAutoloadFileWithBundledDependencies(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => true,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);

        // Should contain require_once for vendor/autoload.php
        $this->assertStringContainsString('vendor/autoload.php', (string) $content);
        $this->assertStringContainsString('require_once', (string) $content);
        $this->assertStringContainsString('file_exists', (string) $content);

        // Should still contain PSR-4 mappings
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', (string) $content);
    }

    public function testRegenerateAutoloadFileWithMixedBundledAndNonBundled(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => true,
            ],
            [
                'namespace'        => 'Tests\Support\Modules\Comments',
                'path'             => TESTPATH . '_support/modules/comments/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);

        // Should contain bundled autoloader for posts
        $this->assertStringContainsString('vendor/autoload.php', (string) $content);
        $this->assertStringContainsString('require_once', (string) $content);

        // Should contain PSR-4 mappings for both
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', (string) $content);
        // Path must be valid
        $this->assertStringNotContainsString('Tests\\\\Support\\\\Modules\\\\Comments', (string) $content);
    }

    public function testRegenerateAutoloadFileSkipsModulesWithMissingNamespace(): void
    {
        $modulesData = [
            [
                'namespace'        => '',
                'path'             => TESTPATH . '_support/modules/invalid/src',
                'has_bundled_deps' => false,
            ],
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);

        // Should not contain invalid module
        $this->assertStringNotContainsString('invalid', (string) $content);

        // Should contain valid module
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', (string) $content);
    }

    public function testRegenerateAutoloadFileSkipsModulesWithMissingPath(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Invalid',
                'path'             => '',
                'has_bundled_deps' => false,
            ],
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);

        // Should not contain invalid module
        $this->assertStringNotContainsString('Tests\\\\Support\\\\Modules\\\\Invalid', (string) $content);

        // Should contain valid module
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', (string) $content);
    }

    public function testRegenerateAutoloadFileWithEmptyArray(): void
    {
        $this->autoloadManager->regenerateAutoloadFile([]);

        $this->assertFileDoesNotExist($this->autoloadFile);
    }

    public function testRegenerateAutoloadFileIsValidPHP(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        // Include the file - should not throw parse error
        $result = include $this->autoloadFile;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Tests\\Support\\Modules\\Posts', $result);
    }

    public function testRegenerateAutoloadFileContainsProperHeader(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);

        // Should contain documentation header
        $this->assertStringContainsString('Modules PSR-4 Cache', (string) $content);
        $this->assertStringContainsString('auto-generated', (string) $content);
        $this->assertStringContainsString('DO NOT EDIT MANUALLY', (string) $content);
    }

    public function testDeleteAutoloadFileRemovesFile(): void
    {
        // First create the file
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);
        $this->assertFileExists($this->autoloadFile);

        // Now delete it
        $this->autoloadManager->deleteAutoloadFile();

        $this->assertFileDoesNotExist($this->autoloadFile);
    }

    public function testDeleteAutoloadFileWhenFileDoesNotExist(): void
    {
        $this->assertFileDoesNotExist($this->autoloadFile);

        // Should not throw exception
        $this->autoloadManager->deleteAutoloadFile();

        $this->assertFileDoesNotExist($this->autoloadFile);
    }

    public function testRegenerateAutoloadFileBundledAutoloadersLoadFirst(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Posts',
                'path'             => TESTPATH . '_support/modules/posts/src',
                'has_bundled_deps' => true,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        $content = file_get_contents($this->autoloadFile);

        // Bundled autoloader require_once should come before the return statement
        $requirePos = strpos($content, 'require_once');
        $returnPos  = strpos($content, 'return [');

        $this->assertNotFalse($requirePos);
        $this->assertNotFalse($returnPos);
        $this->assertLessThan($returnPos, $requirePos, 'Bundled autoloader should load before PSR-4 return');
    }

    public function testRegenerateAutoloadFileWithNonexistentPathLogsWarning(): void
    {
        $modulesData = [
            [
                'namespace'        => 'Tests\Support\Modules\Nonexistent',
                'path'             => TESTPATH . '_support/modules/nonexistent/src',
                'has_bundled_deps' => false,
            ],
        ];

        $this->autoloadManager->regenerateAutoloadFile($modulesData);

        // Should create file but skip nonexistent path
        $this->assertFileExists($this->autoloadFile);

        $content = file_get_contents($this->autoloadFile);

        // Should not contain the nonexistent module
        $this->assertStringNotContainsString('Nonexistent', (string) $content);
    }
}
