<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Keeps the public event invariant: every published event has a schedule. */
final class ArchivePublishedEventsWithoutOccurrences extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "UPDATE events AS events
             LEFT JOIN occurrences AS occurrences
               ON occurrences.event_id = events.id
              AND occurrences.deleted_at IS NULL
             SET events.status = 'draft', events.updated_at = CURRENT_TIMESTAMP
             WHERE events.status = 'published'
               AND events.deleted_at IS NULL
               AND occurrences.id IS NULL"
        );
    }

    public function down(): void
    {
        // The previous publication state is intentionally not reconstructed:
        // these rows were invalid public records and may have been edited after
        // this migration. Restoring them would reintroduce unscheduled events.
    }
}
