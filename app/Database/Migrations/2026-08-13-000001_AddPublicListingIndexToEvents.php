<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * PublicReadEventReader::baseBuilder() always filters
 * `e.status = 'published' AND e.deleted_at IS NULL`, and optionally narrows
 * by `e.event_type` — `events` only had single-column indexes on `status`
 * and `event_type` separately (2026-05-26-002340_CreateEventsTable.php,
 * 2026-07-26-210000_AddEventTypeToEventsTable.php), so MySQL could use at
 * most one of them per query (or an index merge), never a single seek
 * covering the full filter. This composite covers both shapes via leftmost
 * prefix: (status, deleted_at) alone for the unfiltered listing, the full
 * triple when event_type is also filtered.
 *
 * Mirrors catalog-domain's idx_collection_items_public_listing
 * (2026-08-10-000001_AddPublicReadListingIndex.php) and this repo's own
 * idx_occurrences_public_read (2026-08-10-000001_AddPublicReadQueryIndexes.php)
 * — see docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md §2.D.
 */
final class AddPublicListingIndexToEvents extends Migration
{
    public function up(): void
    {
        $this->forge->addKey(
            ['status', 'deleted_at', 'event_type'],
            false,
            false,
            'idx_events_public_listing',
        );
        $this->forge->processIndexes('events');
    }

    public function down(): void
    {
        $this->forge->dropKey('events', 'idx_events_public_listing');
    }
}
