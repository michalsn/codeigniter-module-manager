<?php

declare(strict_types=1);

namespace Tests\Support\Config;

use Michalsn\CodeIgniterModuleManager\Config\ModuleManager as BaseModuleManager;

class ModuleManager extends BaseModuleManager
{
    public string $folderPath = TESTPATH . '_support/modules';
}
