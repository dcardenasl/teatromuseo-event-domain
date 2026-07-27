<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOccurrencesTable extends Migration
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
            'venue_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'start_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'end_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'status' => [
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
        $this->forge->addKey('start_time');
        $this->forge->addKey('end_time');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('event_id', 'events', 'id', 'CASCADE', 'CASCADE', 'fk_occurrences_event_id');
        $this->forge->addForeignKey('venue_id', 'venues', 'id', 'SET NULL', 'CASCADE', 'fk_occurrences_venue_id');
        $this->forge->createTable('occurrences');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('occurrences', 'fk_occurrences_event_id');
        $this->forge->dropForeignKey('occurrences', 'fk_occurrences_venue_id');

        $this->forge->dropTable('occurrences');
    }
}
