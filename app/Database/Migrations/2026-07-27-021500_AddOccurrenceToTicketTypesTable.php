<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddOccurrenceToTicketTypesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('ticket_types', [
            'occurrence_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'event_id',
            ],
        ]);

        $this->db->query('ALTER TABLE ticket_types ADD INDEX idx_ticket_types_occurrence_id (occurrence_id)');
        $this->db->query(
            'ALTER TABLE ticket_types ADD CONSTRAINT fk_ticket_types_occurrence_id '
            . 'FOREIGN KEY (occurrence_id) REFERENCES occurrences (id) '
            . 'ON DELETE SET NULL ON UPDATE CASCADE',
        );
    }

    public function down(): void
    {
        $constraints = $this->db->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ticket_types'
               AND COLUMN_NAME = 'occurrence_id'
               AND REFERENCED_TABLE_NAME = 'occurrences'",
        )->getResultArray();

        foreach ($constraints as $constraint) {
            $name = (string) ($constraint['CONSTRAINT_NAME'] ?? '');
            if ($name !== '') {
                $this->db->query("ALTER TABLE ticket_types DROP FOREIGN KEY `{$name}`");
            }
        }

        $indexes = $this->db->query(
            "SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ticket_types'
               AND COLUMN_NAME = 'occurrence_id'
               AND INDEX_NAME <> 'PRIMARY'",
        )->getResultArray();

        foreach ($indexes as $index) {
            $name = (string) ($index['INDEX_NAME'] ?? '');
            if ($name !== '') {
                $this->db->query("ALTER TABLE ticket_types DROP INDEX `{$name}`");
            }
        }

        $this->forge->dropColumn('ticket_types', 'occurrence_id');
    }
}
