<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Config;

use CodeIgniter\Config\BaseConfig;

class ModuleManager extends BaseConfig
{
    public string $folderPath = ROOTPATH . 'modules';
}
