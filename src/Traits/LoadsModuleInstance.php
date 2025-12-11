<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Traits;

use Michalsn\CodeIgniterModuleManager\BaseModule;
use Michalsn\CodeIgniterModuleManager\Entities\Module;
use Michalsn\CodeIgniterModuleManager\Exceptions\ModuleException;

trait LoadsModuleInstance
{
    /**
     * Load a module instance
     * Returns the module's Module class instance
     */
    private function loadModuleInstance(Module $module): BaseModule
    {
        if (! $module->namespace) {
            throw ModuleException::forNoNamespaceFound($module->folder_name);
        }

        $moduleFilePath = $this->config->folderPath . DIRECTORY_SEPARATOR . $module->folder_name . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php';

        if (! file_exists($moduleFilePath)) {
            throw ModuleException::forModuleFileNotFound($module->folder_name);
        }

        require_once $moduleFilePath;

        $className = $module->namespace . '\\Module';

        if (! class_exists($className)) {
            throw ModuleException::forModuleClassNotFound($className, $module->folder_name);
        }

        $instance = new $className();

        if (! $instance instanceof BaseModule) {
            throw ModuleException::forModuleMustExtendBaseModule($className);
        }

        return $instance;
    }
}
