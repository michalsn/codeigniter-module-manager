<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Entities;

use CodeIgniter\Entity\Entity;

class Module extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at',
        'updated_at',
    ];

    public function isUpdateAvailable(): bool
    {
        if (! $this->is_installed || empty($this->installed_version)) {
            return false;
        }

        return version_compare($this->version, $this->installed_version, '>');
    }

    public function canBeEnabled(): bool
    {
        return $this->is_installed && ! $this->is_enabled;
    }

    public function canBeDisabled(): bool
    {
        return $this->is_installed && $this->is_enabled;
    }

    public function canBeUninstalled(): bool
    {
        return $this->is_installed && ! $this->is_enabled;
    }

    public function getDisplayName(): string
    {
        return $this->name ?: $this->folder_name;
    }

    public function getFullNamespace(): string
    {
        return $this->namespace ?: '';
    }

    public function hasValidModule(): bool
    {
        return ! empty($this->name) && ! empty($this->version) && ! empty($this->folder_name);
    }
}
