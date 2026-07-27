<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'start_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'end_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'venue' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'capacity' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'available_spots' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('title');
        $this->forge->addKey('start_time');
        $this->forge->addKey('end_time');
        $this->forge->addKey('venue');
        $this->forge->addKey('capacity');
        $this->forge->addKey('available_spots');
        $this->forge->addKey('status');
        $this->forge->createTable('events');
    }

    public function down(): void
    {
        $this->forge->dropTable('events');
    }
}
