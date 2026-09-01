<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/** Makes ticket inventory unambiguously belong to one scheduled occurrence. */
final class MakeTicketTypeOccurrenceRequired extends Migration
{
    public function up(): void
    {
        $missing = (int) ($this->db->query(
            'SELECT COUNT(*) AS total FROM ticket_types WHERE occurrence_id IS NULL'
        )->getRow()->total ?? 0);

        if ($missing > 0) {
            throw new RuntimeException(
                "Cannot make ticket_types.occurrence_id required: {$missing} rows still need an occurrence. "
                . 'Repair or quarantine those ticket types before migrating.'
            );
        }

        $this->dropOccurrenceForeignKeys();
        $this->db->query(
            'ALTER TABLE ticket_types MODIFY occurrence_id INT UNSIGNED NOT NULL'
        );
        $this->db->query(
            'ALTER TABLE ticket_types ADD CONSTRAINT fk_ticket_types_occurrence_id '
            . 'FOREIGN KEY (occurrence_id) REFERENCES occurrences (id) '
            . 'ON DELETE CASCADE ON UPDATE CASCADE'
        );
    }

    public function down(): void
    {
        $this->dropOccurrenceForeignKeys();
        $this->db->query(
            'ALTER TABLE ticket_types MODIFY occurrence_id INT UNSIGNED NULL'
        );
        $this->db->query(
            'ALTER TABLE ticket_types ADD CONSTRAINT fk_ticket_types_occurrence_id '
            . 'FOREIGN KEY (occurrence_id) REFERENCES occurrences (id) '
            . 'ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    private function dropOccurrenceForeignKeys(): void
    {
        $constraints = $this->db->query(
            "SELECT DISTINCT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ticket_types'
               AND COLUMN_NAME = 'occurrence_id'
               AND REFERENCED_TABLE_NAME = 'occurrences'"
        )->getResultArray();

        foreach ($constraints as $constraint) {
            $name = (string) ($constraint['CONSTRAINT_NAME'] ?? '');
            if ($name !== '') {
                $this->db->query("ALTER TABLE ticket_types DROP FOREIGN KEY `{$name}`");
            }
        }
    }
}
