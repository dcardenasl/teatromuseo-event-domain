<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTicketsTable extends Migration
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
            'booking_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'ticket_type_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'holder_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'holder_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'qr_code_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'checked_in_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('ticket_type_id');
        $this->forge->addKey('holder_name');
        $this->forge->addKey('holder_email');
        $this->forge->addUniqueKey('qr_code_token');
        $this->forge->addKey('status');
        $this->forge->addKey('checked_in_at');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE', 'fk_tickets_booking_id');
        $this->forge->addForeignKey('ticket_type_id', 'ticket_types', 'id', 'CASCADE', 'RESTRICT', 'fk_tickets_ticket_type_id');
        $this->forge->createTable('tickets');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('tickets', 'fk_tickets_booking_id');
        $this->forge->dropForeignKey('tickets', 'fk_tickets_ticket_type_id');

        $this->forge->dropTable('tickets');
    }
}
