<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTicketTypesTable extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
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
            'sales_start' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'sales_end' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('event_id');
        $this->forge->addKey('name');
        $this->forge->addKey('price');
        $this->forge->addKey('capacity');
        $this->forge->addKey('available_spots');
        $this->forge->addKey('sales_start');
        $this->forge->addKey('sales_end');
        $this->forge->addForeignKey('event_id', 'events', 'id', 'CASCADE', 'CASCADE', 'fk_ticket_types_event_id');
        $this->forge->createTable('ticket_types');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('ticket_types', 'fk_ticket_types_event_id');

        $this->forge->dropTable('ticket_types');
    }
}
