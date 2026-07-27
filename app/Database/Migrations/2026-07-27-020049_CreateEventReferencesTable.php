<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventReferencesTable extends Migration
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
            'event_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'source_system' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'source_type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'source_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'relation' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
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
        $this->forge->addKey('source_system');
        $this->forge->addKey('source_type');
        $this->forge->addKey('source_id');
        $this->forge->addKey('relation');
        $this->forge->addForeignKey('event_id', 'events', 'id', 'CASCADE', 'CASCADE', 'fk_event_references_event_id');
        $this->forge->createTable('event_references');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('event_references', 'fk_event_references_event_id');

        $this->forge->dropTable('event_references');
    }
}
