<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddPublicReadQueryIndexes extends Migration
{
    public function up(): void
    {
        $this->forge->addKey(
            ['event_id', 'deleted_at', 'start_time'],
            false,
            false,
            'idx_occurrences_public_read',
        );
        $this->forge->processIndexes('occurrences');
    }

    public function down(): void
    {
        // MySQL may use the new composite key as the supporting index for the
        // existing event foreign key. Drop and recreate that constraint around
        // the index removal so rollback remains valid on every schema state.
        $this->db->query('ALTER TABLE `occurrences` DROP FOREIGN KEY `fk_occurrences_event_id`');
        $this->forge->dropKey('occurrences', 'idx_occurrences_public_read');
        $this->db->query(
            'ALTER TABLE `occurrences` ADD CONSTRAINT `fk_occurrences_event_id` '
            . 'FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) '
            . 'ON DELETE CASCADE ON UPDATE CASCADE',
        );
    }
}
