<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use Michalsn\CodeIgniterModuleManager\BaseModule;
use Tests\Support\Modules\Posts\Module as PostsModule;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class BaseModuleTest extends TestCase
{
    private PostsModule $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->module = new PostsModule();
    }

    public function testGetInfoReturnsCompleteArray(): void
    {
        $info = $this->module->getInfo();

        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('description', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('url', $info);
    }

    public function testGetNameReturnsModuleName(): void
    {
        $name = $this->module->getName();

        $this->assertSame('Posts', $name);
    }

    public function testGetDescriptionReturnsModuleDescription(): void
    {
        $description = $this->module->getDescription();

        $this->assertNotEmpty($description);
    }

    public function testGetVersionReturnsModuleVersion(): void
    {
        $version = $this->module->getVersion();

        $this->assertSame('1.0.0', $version);
    }

    public function testGetAuthorReturnsModuleAuthor(): void
    {
        $author = $this->module->getAuthor();

        $this->assertNotEmpty($author);
    }

    public function testGetUrlReturnsModuleUrl(): void
    {
        $url = $this->module->getUrl();

        $this->assertIsString($url);
    }

    public function testGetUrlCanBeNull(): void
    {
        // Create a mock module without URL
        $module = new class () extends BaseModule {
            protected string $name        = 'Test';
            protected string $description = 'Test';
            protected string $version     = '1.0.0';
            protected string $author      = 'Test';
        };

        $url = $module->getUrl();

        $this->assertNull($url);
    }

    public function testOnInstallCanBeCalled(): void
    {
        $this->expectNotToPerformAssertions();
        // Should not throw exception
        $this->module->onInstall();
    }

    public function testOnUninstallCanBeCalled(): void
    {
        $this->expectNotToPerformAssertions();
        // Should not throw exception
        $this->module->onUninstall();
    }

    public function testOnEnableCanBeCalled(): void
    {
        $this->expectNotToPerformAssertions();
        // Should not throw exception
        $this->module->onEnable();
    }

    public function testOnDisableCanBeCalled(): void
    {
        $this->expectNotToPerformAssertions();
        // Should not throw exception
        $this->module->onDisable();
    }

    public function testOnUpdateCanBeCalled(): void
    {
        $this->expectNotToPerformAssertions();
        // Should not throw exception
        $this->module->onUpdate('1.0.0', '1.1.0');
    }

    public function testGetRequiredCIVersionReturnsString(): void
    {
        $version = $this->module->getRequiredCIVersion();

        $this->assertIsString($version);
    }

    public function testGetRequiredCIVersionCanReturnNull(): void
    {
        // Create a mock module without required CI version
        $module = new class () extends BaseModule {
            protected string $name        = 'Test';
            protected string $description = 'Test';
            protected string $version     = '1.0.0';
            protected string $author      = 'Test';
        };

        $version = $module->getRequiredCIVersion();

        $this->assertNull($version);
    }

    public function testGetDependenciesReturnsEmptyArrayByDefault(): void
    {
        $dependencies = $this->module->getDependencies();

        $this->assertEmpty($dependencies);
    }

    public function testHasBundledDependenciesReturnsFalseByDefault(): void
    {
        $hasBundled = $this->module->hasBundledDependencies();

        $this->assertFalse($hasBundled);
    }

    public function testHasBundledDependenciesCanReturnTrue(): void
    {
        // Create a mock module with bundled dependencies
        $module = new class () extends BaseModule {
            protected string $name        = 'Test';
            protected string $description = 'Test';
            protected string $version     = '1.0.0';
            protected string $author      = 'Test';

            public function hasBundledDependencies(): bool
            {
                return true;
            }
        };

        $hasBundled = $module->hasBundledDependencies();

        $this->assertTrue($hasBundled);
    }

    public function testConstructorThrowsExceptionForMissingName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Required module properties not properly defined');

        new class () extends BaseModule {
            protected string $description = 'Test';
            protected string $version     = '1.0.0';
            protected string $author      = 'Test';
        };
    }

    public function testConstructorThrowsExceptionForMissingDescription(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Required module properties not properly defined');

        new class () extends BaseModule {
            protected string $name    = 'Test';
            protected string $version = '1.0.0';
            protected string $author  = 'Test';
        };
    }

    public function testConstructorThrowsExceptionForMissingVersion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Required module properties not properly defined');

        new class () extends BaseModule {
            protected string $name        = 'Test';
            protected string $description = 'Test';
            protected string $author      = 'Test';
        };
    }

    public function testConstructorThrowsExceptionForMissingAuthor(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Required module properties not properly defined');

        new class () extends BaseModule {
            protected string $name        = 'Test';
            protected string $description = 'Test';
            protected string $version     = '1.0.0';
        };
    }

    public function testConstructorAcceptsAllRequiredFields(): void
    {
        $module = new class () extends BaseModule {
            protected string $name        = 'Test';
            protected string $description = 'Test Description';
            protected string $version     = '1.0.0';
            protected string $author      = 'Test Author';
        };

        $this->assertSame('Test', $module->getName());
        $this->assertSame('Test Description', $module->getDescription());
        $this->assertSame('1.0.0', $module->getVersion());
        $this->assertSame('Test Author', $module->getAuthor());
    }
}
