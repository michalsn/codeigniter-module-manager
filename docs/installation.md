# Installation

- [Composer Installation](#composer-installation)
- [Manual Installation](#manual-installation)

## Composer Installation

Install via Composer:

```console
composer require michalsn/codeigniter-module-manager
```

## Manual Installation

If you prefer manual installation, follow these steps:

1. Download this project
2. Place the files in `app/ThirdParty/module-manager` directory
3. Enable the package by editing `app/Config/Autoload.php`:

```php
<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
    // ...

    public $psr4 = [
        APP_NAMESPACE => APPPATH,
        'Michalsn\CodeIgniterModuleManager' => APPPATH . 'ThirdParty/module-manager/src',
    ];

    // ...
}
```
