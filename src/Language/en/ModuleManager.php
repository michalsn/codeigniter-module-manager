<?php

return [
    // Module Not Found
    'moduleNotFound'          => 'Module not found: {0}',
    'moduleNotFoundScanFirst' => 'Module not found: {0}. Run \'php spark module:scan\' first.',

    // Module State
    'moduleCannotBeEnabled'        => 'Module cannot be enabled: {0}',
    'moduleCannotBeDisabled'       => 'Module cannot be disabled: {0}',
    'moduleCannotBeUninstalled'    => 'Module cannot be uninstalled (must be disabled first): {0}',
    'moduleAlreadyInstalled'       => 'Module is already installed: {0}',
    'moduleMustBeInstalled'        => 'Module must be installed before enabling',
    'cannotUninstallEnabledModule' => 'Cannot uninstall enabled module. Disable it first.',

    // Requirements
    'requiresNewerCodeIgniterVersion' => 'Module requires CodeIgniter {0} or newer (current: {1})',
    'requiresCodeIgniterVersionRange' => 'Module requires CodeIgniter between {0} and {1} (current: {2})',
    'invalidVersionRangeFormat'       => 'Invalid version range format. Expected array with exactly 2 elements [min, max]',
    'missingDependencies'             => 'Module has missing dependencies: {0}',
    'dependencyNotInstalled'          => 'Required module \'{0}\' is not installed',
    'dependencyNotEnabled'            => 'Required module \'{0}\' is not enabled',
    'dependencyVersionTooOld'         => 'Module \'{0}\' version {1} is too old (requires {2} or newer)',
    'dependencyVersionOutOfRange'     => 'Module \'{0}\' version {1} is outside required range {2} to {3}',
    'invalidDependencyVersionFormat'  => 'Invalid version format for dependency \'{0}\'. Expected string, array, or null',

    // Updates
    'noUpdateAvailable'                 => 'No update available for module: {0}',
    'moduleMustBeInstalledBeforeUpdate' => 'Module must be installed before updating',
    'updateFailed'                      => 'Update failed: {0}',
    'moduleUpdateMethodFailed'          => 'Module update method failed: {0}',
    'failedToUpdateVersionInDatabase'   => 'Failed to update module version in database',

    // Scanner/Structure
    'moduleFolderNotFound'          => 'Module folder does not exist',
    'moduleMustHaveSrcFolder'       => 'Module must have \'src\' folder in: {0}/',
    'moduleFileNotFound'            => 'Module file \'Module.php\' not found in: {0}/src/',
    'noNamespaceFound'              => 'No namespace found in module file: {0}/Module.php',
    'moduleClassNotFound'           => 'Module class \'{0}\' not found in: {1}/Module.php',
    'moduleMustExtendBaseModule'    => 'Module class \'{0}\' must extend BaseModule',
    'modulePropertyNameRequired'    => 'Module property \'name\' is required but not defined',
    'modulePropertyDescRequired'    => 'Module property \'description\' is required but not defined',
    'modulePropertyVersionRequired' => 'Module property \'version\' is required but not defined',
    'modulePropertyVersionInvalid'  => 'Module property \'version\' must be a valid semantic version (e.g., 1.0.0)',
    'modulePropertyAuthorRequired'  => 'Module property \'author\' is required but not defined',

    // Migrations
    'migrationFailed'                 => 'Migration failed: {0}',
    'specificMigrationFailed'         => 'Migration {0} failed: {1}',
    'migrationRollbackFailed'         => 'Failed to rollback migration {0}: {1}',
    'migrationRollbackGeneralFailure' => 'Migration rollback failed: {0}',

    // Database
    'failedToMarkAsInstalled'   => 'Failed to mark module as installed in database',
    'failedToMarkAsUninstalled' => 'Failed to mark module as uninstalled in database',
    'failedToMarkAsEnabled'     => 'Failed to mark module as enabled in database',
    'failedToMarkAsDisabled'    => 'Failed to mark module as disabled in database',

    // Autoload
    'failedToWriteTempAutoloadFile'  => 'Failed to write temporary autoload file',
    'failedToRenameTempAutoloadFile' => 'Failed to rename temporary autoload file',
    'failedToDeleteAutoloadFile'     => 'Failed to delete autoload file',
    'failedToRegenerateAutoload'     => 'Failed to regenerate autoload: {0}',

    // Installation/Uninstallation
    'installationFailed'   => 'Installation failed: {0}',
    'uninstallationFailed' => 'Uninstallation failed: {0}',
    'enableFailed'         => 'Enable failed: {0}',
    'disableFailed'        => 'Disable failed: {0}',
];
