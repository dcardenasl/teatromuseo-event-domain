<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Backfills the other human-facing resources covered by the localized
 * content contract. Kept separate so installations that already applied the
 * translation schema receive the same compatibility treatment safely.
 */
class BackfillEventDomainTranslations extends Migration
{
    public function up(): void
    {
        $this->backfill('venues', 'venue', ['name', 'description']);
        $this->backfill('ticket_types', 'ticket_type', ['name']);
    }

    public function down(): void
    {
        // Deliberately preserve migrated content on rollback. Translation
        // rows may have been edited after this compatibility backfill.
    }

    /**
     * @param list<string> $fields
     */
    private function backfill(string $table, string $resourceType, array $fields): void
    {
        $records = $this->db->table($table)->get()->getResultArray();
        $legacyLocale = config('Localization')->legacyFallbackLocale;
        $now = date('Y-m-d H:i:s');

        foreach ($records as $record) {
            $resourceId = (int) ($record['id'] ?? 0);
            if ($resourceId < 1) {
                continue;
            }

            $hasTranslations = $this->db->table('event_translations')
                ->where([
                    'translatable_type' => $resourceType,
                    'translatable_id' => $resourceId,
                ])
                ->countAllResults() > 0;
            if ($hasTranslations) {
                continue;
            }

            $rows = [];
            foreach ($fields as $field) {
                $value = trim((string) ($record[$field] ?? ''));
                if ($value === '') {
                    continue;
                }

                $rows[] = [
                    'translatable_type' => $resourceType,
                    'translatable_id' => $resourceId,
                    'locale' => $legacyLocale,
                    'field' => $field,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                $this->db->table('event_translations')->insertBatch($rows);
            }
        }
    }
}
