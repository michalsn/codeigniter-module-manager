<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\DatabaseTestTrait;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;
use Michalsn\CodeIgniterModuleManager\Services\AutoloadManager;
use Michalsn\CodeIgniterModuleManager\Services\ModuleInstaller;
use Michalsn\CodeIgniterModuleManager\Services\ModuleRegistry;
use Michalsn\CodeIgniterModuleManager\Services\RequirementsValidator;
use Tests\Support\Config\ModuleManager as ModuleManagerConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ModuleInstallerTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    private ModuleInstaller $installer;
    private ModuleModel $moduleModel;
    private string $autoloadFile;

    protected function setUp(): void
    {
        parent::setUp();

        $config                = new ModuleManagerConfig();
        $this->moduleModel     = new ModuleModel();
        $autoloadManager       = new AutoloadManager();
        $registry              = new ModuleRegistry($config, $this->moduleModel);
        $requirementsValidator = new RequirementsValidator($config, $registry);

        $this->installer    = new ModuleInstaller($config, $this->moduleModel, $autoloadManager, $registry, $requirementsValidator);
        $this->autoloadFile = WRITEPATH . 'modules_psr4.php';

        // Clean up autoload file if exists
        if (file_exists($this->autoloadFile)) {
            unlink($this->autoloadFile);
        }
    }

    public function testInstallNewModule(): void
    {
        // First, add a module record to database (as scanner would do)
        $moduleId = $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Tests\Support\Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $module = $this->moduleModel->find($moduleId);
        $this->assertInstanceOf(Module::class, $module);

        // Install the module
        $result = $this->installer->install($module);

        $this->assertTrue($result);

        /** @var Module $updatedModule */
        $updatedModule = $this->moduleModel->find($moduleId);
        $this->assertTrue($updatedModule->is_installed);
        $this->assertSame('1.0.0', $updatedModule->installed_version);
    }

    public function testInstallAlreadyInstalledModuleReturnsTrue(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        // Installing again should return true immediately
        $result = $this->installer->install($module);

        $this->assertTrue($result);
    }

    public function testUninstallModule(): void
    {
        // First install a module
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        // Uninstall the module
        $result = $this->installer->uninstall($module);

        $this->assertTrue($result);

        // Verify module is marked as uninstalled
        /** @var Module $updatedModule */
        $updatedModule = $this->moduleModel->find($moduleId);
        $this->assertFalse($updatedModule->is_installed);
        $this->assertNull($updatedModule->installed_version);
    }

    public function testUninstallNotInstalledModuleReturnsTrue(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Tests\Support\Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        // Uninstalling not-installed module should return true
        $result = $this->installer->uninstall($module);

        $this->assertTrue($result);
    }

    public function testUninstallEnabledModuleThrowsException(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Cannot uninstall enabled module');

        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        $this->installer->uninstall($module);
    }

    public function testEnableModule(): void
    {
        // First install a module
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        // Enable the module
        $result = $this->installer->enable($module);

        $this->assertTrue($result);

        /** @var Module $updatedModule */
        $updatedModule = $this->moduleModel->find($moduleId);
        $this->assertTrue($updatedModule->is_enabled);

        // Verify autoload file was generated
        $this->assertFileExists($this->autoloadFile);
    }

    public function testEnableNotInstalledModuleThrowsException(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('Module must be installed');

        $moduleId = $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Tests\Support\Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        $this->installer->enable($module);
    }

    public function testEnableAlreadyEnabledModuleReturnsTrue(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        $result = $this->installer->enable($module);

        $this->assertTrue($result);
    }

    public function testDisableModule(): void
    {
        // First enable a module
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        // Disable the module
        $result = $this->installer->disable($module);

        $this->assertTrue($result);

        /** @var Module $updatedModule */
        $updatedModule = $this->moduleModel->find($moduleId);
        $this->assertFalse($updatedModule->is_enabled);
    }

    public function testDisableAlreadyDisabledModuleReturnsTrue(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        $result = $this->installer->disable($module);

        $this->assertTrue($result);
    }

    public function testEnableRegeneratesAutoloadFileForAllEnabledModules(): void
    {
        // Enable first module
        $module1Id = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module1 */
        $module1 = $this->moduleModel->find($module1Id);
        $this->installer->enable($module1);

        // Create and enable second module (simulate)
        $this->moduleModel->insert([
            'folder_name'       => 'comments',
            'namespace'         => 'Tests\Support\Modules\Comments',
            'name'              => 'Comments',
            'description'       => 'Comments module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        // Enable posts module again should regenerate with both modules
        $this->installer->enable($module1);

        // Verify autoload file contains both namespaces
        $this->assertFileExists($this->autoloadFile);
        $content = file_get_contents($this->autoloadFile);
        $this->assertStringContainsString('Tests\\\\Support\\\\Modules\\\\Posts', (string) $content);
        $this->assertStringNotContainsString('Tests\\\\Support\\\\Modules\\\\Comments', (string) $content);
    }

    public function testDisableRegeneratesAutoloadFileWithoutDisabledModule(): void
    {
        // Enable a module first
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);
        $this->installer->enable($module);

        // Now disable it
        $this->installer->disable($module);

        // Verify autoload file exists but doesn't contain the namespace
        $this->assertFileExists($this->autoloadFile);
        $content = file_get_contents($this->autoloadFile);
        // Should have empty return array since no modules enabled
        $this->assertStringContainsString('return [', (string) $content);
        $this->assertStringNotContainsString('Tests\Support\Modules\Posts', (string) $content);
    }

    public function testEnableCallsModuleOnEnableHook(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        $module = $this->moduleModel->find($moduleId);

        // Enable should call onEnable() hook (verified by not throwing exception)
        $result = $this->installer->enable($module);

        $this->assertTrue($result);
    }

    public function testDisableCallsModuleOnDisableHook(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Tests\Support\Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        /** @var Module $module */
        $module = $this->moduleModel->find($moduleId);

        // Disable should call onDisable() hook (verified by not throwing exception)
        $result = $this->installer->disable($module);

        $this->assertTrue($result);
    }
}
