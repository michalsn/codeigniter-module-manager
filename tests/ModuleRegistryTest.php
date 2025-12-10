<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\DatabaseTestTrait;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Models\ModuleModel;
use Michalsn\CodeIgniterModuleManager\Services\ModuleRegistry;
use Tests\Support\Config\ModuleManager as ModuleManagerConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ModuleRegistryTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    private ModuleRegistry $registry;
    private ModuleModel $moduleModel;
    private ModuleManagerConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config      = new ModuleManagerConfig();
        $this->moduleModel = new ModuleModel();
        $this->registry    = new ModuleRegistry($this->config, $this->moduleModel);
    }

    public function testGetByFolderName(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $module = $this->registry->getByFolderName('posts');

        $this->assertInstanceOf(Module::class, $module);
        $this->assertSame('posts', $module->folder_name);
        $this->assertSame('Modules\Posts', $module->namespace);
    }

    public function testGetByFolderNameCachesResult(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // First call - hits database
        $this->registry->getByFolderName('posts');

        // Delete from database
        $this->moduleModel->delete($moduleId);

        // Second call - should return cached result
        $module2 = $this->registry->getByFolderName('posts');

        $this->assertInstanceOf(Module::class, $module2);
        $this->assertSame('posts', $module2->folder_name);
    }

    public function testGetByFolderNameReturnsNullForNonexistent(): void
    {
        $module = $this->registry->getByFolderName('nonexistent');

        $this->assertNotInstanceOf(Module::class, $module);
    }

    public function testGetByFolderNameCachesNullResults(): void
    {
        // First call - hits database
        $module1 = $this->registry->getByFolderName('nonexistent');
        $this->assertNotInstanceOf(Module::class, $module1);

        // Insert module after first query
        $this->moduleModel->insert([
            'folder_name' => 'nonexistent',
            'namespace'   => 'Modules\Nonexistent',
            'name'        => 'Nonexistent',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // Second call - should still return null from cache
        $module2 = $this->registry->getByFolderName('nonexistent');
        $this->assertNotInstanceOf(Module::class, $module2);
    }

    public function testGetByNamespace(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $module = $this->registry->getByNamespace('Modules\Posts');

        $this->assertInstanceOf(Module::class, $module);
        $this->assertSame('Modules\Posts', $module->namespace);
        $this->assertSame('posts', $module->folder_name);
    }

    public function testGetByNamespaceCachesResult(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // First call
        $this->registry->getByNamespace('Modules\Posts');

        // Delete from database
        $this->moduleModel->delete($moduleId);

        // Second call - should return cached
        $module2 = $this->registry->getByNamespace('Modules\Posts');

        $this->assertInstanceOf(Module::class, $module2);
    }

    public function testGetByNamespaceAlsoCachesByFolderName(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // Get by namespace first
        $this->registry->getByNamespace('Modules\Posts');

        // Now get by folder name - should be cached
        $stats = $this->registry->getCacheStats();
        $this->assertSame(1, $stats['cached_by_folder']);
        $this->assertSame(1, $stats['cached_by_namespace']);
    }

    public function testGetWithNamespaceCallsGetByNamespace(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $module = $this->registry->get('Modules\Posts');

        $this->assertInstanceOf(Module::class, $module);
        $this->assertSame('Modules\Posts', $module->namespace);
    }

    public function testGetWithFolderNameCallsGetByFolderName(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $module = $this->registry->get('posts');

        $this->assertInstanceOf(Module::class, $module);
        $this->assertSame('posts', $module->folder_name);
    }

    public function testHasReturnsTrueForExistingModule(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $this->assertTrue($this->registry->has('posts'));
        $this->assertTrue($this->registry->has('Modules\Posts'));
    }

    public function testHasReturnsFalseForNonexistentModule(): void
    {
        $this->assertFalse($this->registry->has('nonexistent'));
        $this->assertFalse($this->registry->has('Modules\Nonexistent'));
    }

    public function testAllReturnsAllModules(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $this->moduleModel->insert([
            'folder_name' => 'comments',
            'namespace'   => 'Modules\Comments',
            'name'        => 'Comments',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $modules = $this->registry->all();

        $this->assertCount(2, $modules);
        $this->assertContainsOnlyInstancesOf(Module::class, $modules);
    }

    public function testAllCachesResults(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // First call
        $modules1 = $this->registry->all();
        $count1   = count($modules1);

        // Insert new module
        $this->moduleModel->insert([
            'folder_name' => 'comments',
            'namespace'   => 'Modules\Comments',
            'name'        => 'Comments',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // Second call - should return cached result
        $modules2 = $this->registry->all();

        $this->assertCount($count1, $modules2);
    }

    public function testInstalledReturnsOnlyInstalledModules(): void
    {
        $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        $this->moduleModel->insert([
            'folder_name' => 'comments',
            'namespace'   => 'Modules\Comments',
            'name'        => 'Comments',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        $modules = $this->registry->installed();

        $this->assertCount(1, $modules);
        $this->assertTrue($modules[0]->is_installed);
    }

    public function testEnabledReturnsOnlyEnabledModules(): void
    {
        $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        $this->moduleModel->insert([
            'folder_name'       => 'comments',
            'namespace'         => 'Modules\Comments',
            'name'              => 'Comments',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        $modules = $this->registry->enabled();

        $this->assertCount(1, $modules);
        $this->assertTrue($modules[0]->is_enabled);
    }

    public function testDisabledReturnsOnlyDisabledModules(): void
    {
        $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'is_enabled'        => true,
            'installed_version' => '1.0.0',
        ]);

        $this->moduleModel->insert([
            'folder_name'       => 'comments',
            'namespace'         => 'Modules\Comments',
            'name'              => 'Comments',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        $modules = $this->registry->disabled();

        $this->assertCount(1, $modules);
        $this->assertFalse($modules[0]->is_enabled);
    }

    public function testUpdatableReturnsModulesWithUpdates(): void
    {
        $this->moduleModel->insert([
            'folder_name'       => 'posts',
            'namespace'         => 'Modules\Posts',
            'name'              => 'Posts',
            'description'       => 'Test module',
            'version'           => '2.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        $this->moduleModel->insert([
            'folder_name'       => 'comments',
            'namespace'         => 'Modules\Comments',
            'name'              => 'Comments',
            'description'       => 'Test module',
            'version'           => '1.0.0',
            'author'            => 'Test',
            'is_installed'      => true,
            'installed_version' => '1.0.0',
        ]);

        $modules = $this->registry->updatable();

        $this->assertCount(1, $modules);
        $this->assertSame('posts', $modules[0]->folder_name);
    }

    public function testRefreshClearsAllCaches(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // Populate caches
        $this->registry->getByFolderName('posts');
        $this->registry->all();

        $stats = $this->registry->getCacheStats();
        $this->assertGreaterThan(0, $stats['cached_by_folder']);
        $this->assertNotEmpty($stats['cached_lists']);

        // Refresh
        $this->registry->refresh();

        $stats = $this->registry->getCacheStats();
        $this->assertSame(0, $stats['cached_by_folder']);
        $this->assertSame(0, $stats['cached_by_namespace']);
        $this->assertEmpty($stats['cached_lists']);
    }

    public function testInvalidateRemovesSpecificModuleFromCache(): void
    {
        $moduleId = $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // Cache the module
        $module = $this->registry->getByFolderName('posts');
        $this->assertInstanceOf(Module::class, $module);

        // Update in database
        $this->moduleModel->update($moduleId, ['version' => '2.0.0']);

        // Invalidate cache
        $this->registry->invalidate('posts');

        // Should fetch fresh data
        $updatedModule = $this->registry->getByFolderName('posts');
        $this->assertSame('2.0.0', $updatedModule->version);
    }

    public function testInvalidateClearsListCaches(): void
    {
        $this->moduleModel->insert([
            'folder_name' => 'posts',
            'namespace'   => 'Modules\Posts',
            'name'        => 'Posts',
            'description' => 'Test module',
            'version'     => '1.0.0',
            'author'      => 'Test',
        ]);

        // Cache the list
        $this->registry->all();

        $stats = $this->registry->getCacheStats();
        $this->assertNotEmpty($stats['cached_lists']);

        // Invalidate specific module
        $this->registry->invalidate('posts');

        // List caches should be cleared
        $stats = $this->registry->getCacheStats();
        $this->assertEmpty($stats['cached_lists']);
    }

    public function testGetPathReturnsCorrectPath(): void
    {
        $path = $this->registry->getPath('posts');

        $expectedPath = $this->config->folderPath . DIRECTORY_SEPARATOR . 'posts';
        $this->assertSame($expectedPath, $path);
    }

    public function testGetModulesPathReturnsModulesDirectory(): void
    {
        $path = $this->registry->getModulesPath();

        $this->assertSame($this->config->folderPath, $path);
    }

    public function testGetCacheStatsReturnsCorrectStructure(): void
    {
        $stats = $this->registry->getCacheStats();

        $this->assertArrayHasKey('cached_by_folder', $stats);
        $this->assertArrayHasKey('cached_by_namespace', $stats);
        $this->assertArrayHasKey('cached_lists', $stats);
    }
}
