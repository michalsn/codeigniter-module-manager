<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterModuleManager\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateModulesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'version' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'author' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'folder_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'namespace' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'is_installed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'installed_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('namespace');
        $this->forge->addUniqueKey('folder_name');
        $this->forge->addKey(['is_installed', 'is_enabled']);

        $this->forge->createTable('modules');
    }

    public function down()
    {
        $this->forge->dropTable('modules');
    }
}
