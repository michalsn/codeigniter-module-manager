<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Services;

use DirectoryIterator;
use Exception;
use Michalsn\CodeIgniterModuleManager\BaseModule;
use Michalsn\CodeIgniterModuleManager\Config\ModuleManager as ModuleManagerConfig;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

class ModuleScanner
{
    /**
     * Cache for parsed module files
     *
     * @var array<string, array{namespace: ?string, path: string}>
     */
    private array $fileCache = [];

    public function __construct(private readonly ModuleManagerConfig $config)
    {
    }

    public function scanForModules(): array
    {
        $modules     = [];
        $modulesPath = $this->config->folderPath;

        if (! is_dir($modulesPath)) {
            return $modules;
        }

        $iterator = new DirectoryIterator($modulesPath);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || ! $fileInfo->isDir()) {
                continue;
            }

            $folderName = $fileInfo->getFilename();

            try {
                $moduleData = $this->scanModule($folderName);
                if ($moduleData !== null) {
                    $modules[] = $moduleData;
                }
            } catch (ModuleException $e) {
                // Log invalid modules but continue scanning
                log_message('warning', "Invalid module '{$folderName}': " . $e->getMessage());
            }
        }

        return $modules;
    }

    public function scanModule(string $folderName): ?array
    {
        $modulePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $folderName;

        if (! is_dir($modulePath)) {
            return null;
        }

        // Require src/ folder (modern PHP structure)
        $srcPath = $modulePath . DIRECTORY_SEPARATOR . 'src';
        if (! is_dir($srcPath)) {
            throw ModuleException::forModuleMustHaveSrcFolder($folderName);
        }

        $moduleFilePath = $srcPath . DIRECTORY_SEPARATOR . 'Module.php';

        if (! file_exists($moduleFilePath)) {
            throw ModuleException::forModuleFileNotFound($folderName);
        }

        // Parse file (cached, single read)
        $fileData = $this->parseModuleFile($moduleFilePath);

        // Validate namespace exists
        if (! $fileData['namespace']) {
            throw ModuleException::forNoNamespaceFound($folderName);
        }

        // Load instance with known namespace
        $moduleInstance = $this->loadModuleInstance($moduleFilePath, $folderName, $fileData['namespace']);

        // Get info
        $moduleInfo = $moduleInstance->getInfo();

        return array_merge($moduleInfo, [
            'folder_name' => $folderName,
            'namespace'   => $fileData['namespace'],
        ]);
    }

    /**
     * Parse module file and extract metadata in one pass
     * Returns structured data about the module file
     *
     * @return array{namespace: ?string, path: string}
     */
    private function parseModuleFile(string $moduleFilePath): array
    {
        // Check cache first
        if (isset($this->fileCache[$moduleFilePath])) {
            return $this->fileCache[$moduleFilePath];
        }

        $content = file_get_contents($moduleFilePath);

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/i', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        $result = [
            'namespace' => $namespace,
            'path'      => $moduleFilePath,
        ];

        // Cache the result
        $this->fileCache[$moduleFilePath] = $result;

        return $result;
    }

    /**
     * Load and validate module instance
     * Requires pre-parsed namespace
     */
    private function loadModuleInstance(string $moduleFilePath, string $folderName, string $namespace): BaseModule
    {
        require_once $moduleFilePath;

        $className = $namespace . '\\Module';

        if (! class_exists($className)) {
            throw ModuleException::forModuleClassNotFound($className, $folderName);
        }

        try {
            $instance = new $className();
        } catch (Exception $e) {
            throw ModuleException::forFailedToInstantiateModule($className, $e->getMessage());
        }

        if (! $instance instanceof BaseModule) {
            throw ModuleException::forModuleMustExtendBaseModule($className);
        }

        return $instance;
    }

    public function validateModuleStructure(string $folderName): array
    {
        $errors     = [];
        $modulePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $folderName;

        // Check if folder exists
        if (! is_dir($modulePath)) {
            $errors[] = "Module folder does not exist: {$folderName}";

            return $errors;
        }

        // Check if src/ folder exists (required for modern structure)
        $srcPath = $modulePath . DIRECTORY_SEPARATOR . 'src';
        if (! is_dir($srcPath)) {
            $errors[] = "Module must have 'src' folder: {$folderName}";

            return $errors;
        }

        // Check if Module.php exists in src/
        $moduleFilePath = $srcPath . DIRECTORY_SEPARATOR . 'Module.php';
        if (! file_exists($moduleFilePath)) {
            $errors[] = "Module.php file is missing in: {$folderName}/src/";

            return $errors;
        }

        try {
            // Parse file to get namespace
            $fileData = $this->parseModuleFile($moduleFilePath);

            if (! $fileData['namespace']) {
                throw ModuleException::forNoNamespaceFound($folderName);
            }

            // Load and validate instance
            $this->loadModuleInstance($moduleFilePath, $folderName, $fileData['namespace']);
        } catch (ModuleException $e) {
            $errors[] = $e->getMessage();
        }

        return $errors;
    }

    public function getModuleNamespace(string $folderName): ?string
    {
        $moduleFilePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php';

        if (! file_exists($moduleFilePath)) {
            return null;
        }

        // Use cached parsing
        $fileData = $this->parseModuleFile($moduleFilePath);

        return $fileData['namespace'];
    }
}
