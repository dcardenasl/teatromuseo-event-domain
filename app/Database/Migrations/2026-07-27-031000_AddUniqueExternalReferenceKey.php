<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Makes the cross-domain reference seam idempotent.
 *
 * External references are intentionally stored without foreign keys because
 * their targets live in another domain database. The scoped unique key lets
 * an importer safely re-run the same link without creating duplicates.
 */
final class AddUniqueExternalReferenceKey extends Migration
{
    public function up(): void
    {
        $indexes = $this->db->query('SHOW INDEX FROM event_references')->getResultArray();
        foreach ($indexes as $index) {
            if ((string) ($index['Key_name'] ?? '') === 'uq_event_external_reference') {
                return;
            }
        }

        $this->db->query(
            'ALTER TABLE event_references '
            . 'ADD UNIQUE KEY uq_event_external_reference '
            . '(event_id, source_system(64), source_type(64), source_id(128), relation(64))'
        );
    }

    public function down(): void
    {
        $indexes = $this->db->query('SHOW INDEX FROM event_references')->getResultArray();
        foreach ($indexes as $index) {
            if ((string) ($index['Key_name'] ?? '') === 'uq_event_external_reference') {
                // MySQL may have selected the composite unique key as the
                // supporting index for the event_id foreign key. Guarantee a
                // replacement left-prefix index before removing it so a
                // rollback remains valid on databases with that optimizer
                // choice.
                $hasEventIdIndex = false;
                foreach ($indexes as $candidate) {
                    if ((string) ($candidate['Key_name'] ?? '') === 'idx_event_references_event_id') {
                        $hasEventIdIndex = true;
                        break;
                    }
                }

                if (! $hasEventIdIndex) {
                    $this->db->query(
                        'ALTER TABLE event_references ADD KEY idx_event_references_event_id (event_id)'
                    );
                }

                $this->db->query('ALTER TABLE event_references DROP INDEX uq_event_external_reference');
                break;
            }
        }
    }
}
