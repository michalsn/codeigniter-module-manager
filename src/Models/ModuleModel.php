<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterModuleManager\Entities\Module;

class ModuleModel extends Model
{
    protected $table            = 'modules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Module::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'description',
        'version',
        'author',
        'url',
        'folder_name',
        'namespace',
        'is_installed',
        'is_enabled',
        'installed_version',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = false;
    protected array $casts            = [
        'id'           => 'int',
        'is_installed' => 'bool',
        'is_enabled'   => 'bool',
    ];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'id'          => 'permit_empty|integer',
        'name'        => 'required|max_length[255]',
        'version'     => 'required|max_length[50]',
        'folder_name' => 'required|max_length[255]|is_unique[modules.folder_name,id,{id}]',
        'namespace'   => 'required|max_length[255]|is_unique[modules.namespace,id,{id}]',
        'author'      => 'permit_empty|max_length[255]',
        'url'         => 'permit_empty|max_length[500]|valid_url_strict',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function findByFolderName(string $folderName): ?Module
    {
        return $this->where('folder_name', $folderName)->first();
    }

    public function findByNamespace(string $namespace): ?Module
    {
        return $this->where('namespace', $namespace)->first();
    }

    public function getInstalled(): array
    {
        return $this->where('is_installed', true)->findAll();
    }

    public function getEnabled(): array
    {
        return $this->where('is_enabled', true)->findAll();
    }

    public function getDisabled(): array
    {
        return $this->where('is_installed', true)
            ->where('is_enabled', false)
            ->findAll();
    }

    public function getUpdatable(): array
    {
        $modules = $this->where('is_installed', true)->findAll();

        return array_filter($modules, static fn ($module) => $module->isUpdateAvailable());
    }

    public function upsertByFolderName(array $data): bool
    {
        $folderName = $data['folder_name'] ?? null;

        if (empty($folderName)) {
            return false;
        }

        $existing = $this->findByFolderName($folderName);

        if ($existing instanceof Module) {
            // Preserve installation state during upsert
            $data['is_installed']      = $existing->is_installed;
            $data['is_enabled']        = $existing->is_enabled;
            $data['installed_version'] = $existing->installed_version;
            $data['id']                = $existing->id;

            return $this->update($existing->id, $data);
        }

        return $this->insert($data, false) !== false;
    }

    public function markAsInstalled(int $moduleId, string $version): bool
    {
        return $this->update($moduleId, [
            'is_installed'      => true,
            'installed_version' => $version,
        ]);
    }

    public function markAsUninstalled(int $moduleId): bool
    {
        return $this->update($moduleId, [
            'is_installed'      => false,
            'is_enabled'        => false,
            'installed_version' => null,
        ]);
    }

    public function markAsEnabled(int $moduleId): bool
    {
        return $this->update($moduleId, [
            'is_enabled' => true,
        ]);
    }

    public function markAsDisabled(int $moduleId): bool
    {
        return $this->update($moduleId, [
            'is_enabled' => false,
        ]);
    }
}
