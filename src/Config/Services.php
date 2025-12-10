<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Config;

use CodeIgniter\Config\BaseService;
use Michalsn\CodeIgniterModuleManager\Services\AutoloadManager;
use Michalsn\CodeIgniterModuleManager\Services\ModuleInstaller;
use Michalsn\CodeIgniterModuleManager\Services\ModuleManager;
use Michalsn\CodeIgniterModuleManager\Services\ModuleRegistry;
use Michalsn\CodeIgniterModuleManager\Services\ModuleScanner;
use Michalsn\CodeIgniterModuleManager\Services\ModuleUpdater;
use Michalsn\CodeIgniterModuleManager\Services\RequirementsValidator;

class Services extends BaseService
{
    public static function moduleManager(bool $getShared = true): ModuleManager
    {
        if ($getShared) {
            return static::getSharedInstance('moduleManager');
        }

        return new ModuleManager(
            config('ModuleManager'),
            model('ModuleModel'),
            static::moduleRegistry(),
            static::moduleScanner(),
            static::autoloadManager(),
            static::moduleInstaller(),
            static::moduleUpdater(),
        );
    }

    public static function moduleRegistry(bool $getShared = true): ModuleRegistry
    {
        if ($getShared) {
            return static::getSharedInstance('moduleRegistry');
        }

        return new ModuleRegistry(
            config('ModuleManager'),
            model('ModuleModel'),
        );
    }

    public static function moduleScanner(bool $getShared = true): ModuleScanner
    {
        if ($getShared) {
            return static::getSharedInstance('moduleScanner');
        }

        return new ModuleScanner(
            config('ModuleManager'),
        );
    }

    public static function autoloadManager(bool $getShared = true): AutoloadManager
    {
        if ($getShared) {
            return static::getSharedInstance('autoloadManager');
        }

        return new AutoloadManager();
    }

    public static function requirementsValidator(bool $getShared = true): RequirementsValidator
    {
        if ($getShared) {
            return static::getSharedInstance('requirementsValidator');
        }

        return new RequirementsValidator(
            config('ModuleManager'),
            static::moduleRegistry(),
        );
    }

    public static function moduleInstaller(bool $getShared = true): ModuleInstaller
    {
        if ($getShared) {
            return static::getSharedInstance('moduleInstaller');
        }

        return new ModuleInstaller(
            config('ModuleManager'),
            model('ModuleModel'),
            static::autoloadManager(),
            static::moduleRegistry(),
            static::requirementsValidator(),
        );
    }

    public static function moduleUpdater(bool $getShared = true): ModuleUpdater
    {
        if ($getShared) {
            return static::getSharedInstance('moduleUpdater');
        }

        return new ModuleUpdater(
            config('ModuleManager'),
            model('ModuleModel'),
            static::moduleScanner(),
            static::moduleRegistry(),
            static::requirementsValidator(),
        );
    }
}
