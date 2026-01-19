<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Exceptions;

use RuntimeException;

final class ModuleException extends RuntimeException
{
    // Module Not Found Exceptions
    public static function forModuleNotFound(string $namespace): static
    {
        return new self(lang('ModuleManager.moduleNotFound', [$namespace]));
    }

    public static function forModuleNotFoundScanFirst(string $namespace): static
    {
        return new self(lang('ModuleManager.moduleNotFoundScanFirst', [$namespace]));
    }

    // Module State Exceptions
    public static function forModuleCannotBeEnabled(string $namespace): static
    {
        return new self(lang('ModuleManager.moduleCannotBeEnabled', [$namespace]));
    }

    public static function forModuleCannotBeDisabled(string $namespace): static
    {
        return new self(lang('ModuleManager.moduleCannotBeDisabled', [$namespace]));
    }

    public static function forModuleCannotBeUninstalled(string $namespace): static
    {
        return new self(lang('ModuleManager.moduleCannotBeUninstalled', [$namespace]));
    }

    public static function forModuleAlreadyInstalled(string $namespace): static
    {
        return new self(lang('ModuleManager.moduleAlreadyInstalled', [$namespace]));
    }

    public static function forModuleMustBeInstalled(): static
    {
        return new self(lang('ModuleManager.moduleMustBeInstalled'));
    }

    public static function forCannotUninstallEnabledModule(): static
    {
        return new self(lang('ModuleManager.cannotUninstallEnabledModule'));
    }

    // Requirement Exceptions
    public static function forRequiresNewerCodeIgniterVersion(string $required, string $current): static
    {
        return new self(lang('ModuleManager.requiresNewerCodeIgniterVersion', [$required, $current]));
    }

    public static function forRequiresCodeIgniterVersionRange(string $min, string $max, string $current): static
    {
        return new self(lang('ModuleManager.requiresCodeIgniterVersionRange', [$min, $max, $current]));
    }

    public static function forInvalidVersionRangeFormat(): static
    {
        return new self(lang('ModuleManager.invalidVersionRangeFormat'));
    }

    public static function forMissingDependencies(array $dependencies): static
    {
        return new self(lang('ModuleManager.missingDependencies', [implode(', ', $dependencies)]));
    }

    public static function forDependencyNotInstalled(string $namespace): static
    {
        return new self(lang('ModuleManager.dependencyNotInstalled', [$namespace]));
    }

    public static function forDependencyNotEnabled(string $namespace): static
    {
        return new self(lang('ModuleManager.dependencyNotEnabled', [$namespace]));
    }

    public static function forDependencyVersionTooOld(string $namespace, string $current, string $required): static
    {
        return new self(lang('ModuleManager.dependencyVersionTooOld', [$namespace, $current, $required]));
    }

    public static function forDependencyVersionOutOfRange(string $namespace, string $current, string $min, string $max): static
    {
        return new self(lang('ModuleManager.dependencyVersionOutOfRange', [$namespace, $current, $min, $max]));
    }

    public static function forInvalidDependencyVersionFormat(string $namespace): static
    {
        return new self(lang('ModuleManager.invalidDependencyVersionFormat', [$namespace]));
    }

    // Update Exceptions
    public static function forNoUpdateAvailable(string $namespace): static
    {
        return new self(lang('ModuleManager.noUpdateAvailable', [$namespace]));
    }

    public static function forModuleMustBeInstalledBeforeUpdate(): static
    {
        return new self(lang('ModuleManager.moduleMustBeInstalledBeforeUpdate'));
    }

    public static function forUpdateFailed(string $message): static
    {
        return new self(lang('ModuleManager.updateFailed', [$message]));
    }

    public static function forModuleUpdateMethodFailed(string $message): static
    {
        return new self(lang('ModuleManager.moduleUpdateMethodFailed', [$message]));
    }

    public static function forFailedToUpdateVersionInDatabase(): static
    {
        return new self(lang('ModuleManager.failedToUpdateVersionInDatabase'));
    }

    // Scanner/Structure Exceptions
    public static function forModuleMustHaveSrcFolder(string $folderName): static
    {
        return new self(lang('ModuleManager.moduleMustHaveSrcFolder', [$folderName]));
    }

    public static function forModuleFileNotFound(string $folderName): static
    {
        return new self(lang('ModuleManager.moduleFileNotFound', [$folderName]));
    }

    public static function forNoNamespaceFound(string $folderName): static
    {
        return new self(lang('ModuleManager.noNamespaceFound', [$folderName]));
    }

    public static function forModuleClassNotFound(string $className, string $folderName): static
    {
        return new self(lang('ModuleManager.moduleClassNotFound', [$className, $folderName]));
    }

    public static function forModuleMustExtendBaseModule(string $className): static
    {
        return new self(lang('ModuleManager.moduleMustExtendBaseModule', [$className]));
    }

    public static function forModulePropertyNameRequired(): static
    {
        return new self(lang('ModuleManager.modulePropertyNameRequired'));
    }

    public static function forModulePropertyDescriptionRequired(): static
    {
        return new self(lang('ModuleManager.modulePropertyDescRequired'));
    }

    public static function forModulePropertyVersionRequired(): static
    {
        return new self(lang('ModuleManager.modulePropertyVersionRequired'));
    }

    public static function forModulePropertyVersionInvalid(): static
    {
        return new self(lang('ModuleManager.modulePropertyVersionInvalid'));
    }

    public static function forModulePropertyAuthorRequired(): static
    {
        return new self(lang('ModuleManager.modulePropertyAuthorRequired'));
    }

    // Migration Exceptions
    public static function forMigrationFailed(string $errors): static
    {
        return new self(lang('ModuleManager.migrationFailed', [$errors]));
    }

    public static function forSpecificMigrationFailed(string $migrationName, string $message): static
    {
        return new self(lang('ModuleManager.specificMigrationFailed', [$migrationName, $message]));
    }

    public static function forMigrationRollbackFailed(string $className, string $message): static
    {
        return new self(lang('ModuleManager.migrationRollbackFailed', [$className, $message]));
    }

    public static function forMigrationRollbackGeneralFailure(string $message): static
    {
        return new self(lang('ModuleManager.migrationRollbackGeneralFailure', [$message]));
    }

    // Database Exceptions
    public static function forFailedToMarkAsInstalled(): static
    {
        return new self(lang('ModuleManager.failedToMarkAsInstalled'));
    }

    public static function forFailedToMarkAsUninstalled(): static
    {
        return new self(lang('ModuleManager.failedToMarkAsUninstalled'));
    }

    public static function forFailedToMarkAsEnabled(): static
    {
        return new self(lang('ModuleManager.failedToMarkAsEnabled'));
    }

    public static function forFailedToMarkAsDisabled(): static
    {
        return new self(lang('ModuleManager.failedToMarkAsDisabled'));
    }

    // Autoload Exceptions
    public static function forFailedToWriteTempAutoloadFile(): static
    {
        return new self(lang('ModuleManager.failedToWriteTempAutoloadFile'));
    }

    public static function forFailedToRenameTempAutoloadFile(): static
    {
        return new self(lang('ModuleManager.failedToRenameTempAutoloadFile'));
    }

    public static function forFailedToDeleteAutoloadFile(): static
    {
        return new self(lang('ModuleManager.failedToDeleteAutoloadFile'));
    }

    public static function forFailedToRegenerateAutoload(string $message): static
    {
        return new self(lang('ModuleManager.failedToRegenerateAutoload', [$message]));
    }

    // Installation/Uninstallation Exceptions
    public static function forInstallationFailed(string $message): static
    {
        return new self(lang('ModuleManager.installationFailed', [$message]));
    }

    public static function forUninstallationFailed(string $message): static
    {
        return new self(lang('ModuleManager.uninstallationFailed', [$message]));
    }

    public static function forEnableFailed(string $message): static
    {
        return new self(lang('ModuleManager.enableFailed', [$message]));
    }

    public static function forDisableFailed(string $message): static
    {
        return new self(lang('ModuleManager.disableFailed', [$message]));
    }
}
