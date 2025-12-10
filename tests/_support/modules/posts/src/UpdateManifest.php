<?php

declare(strict_types=1);

namespace Tests\Support\Modules\Posts;

use Michalsn\CodeIgniterModuleManager\BaseUpdateManifest;

class UpdateManifest extends BaseUpdateManifest
{
    protected array $migrations = [
        '1.0.0' => [
            '2025-12-06-120000_CreatePostsTables',
        ],
    ];
}
