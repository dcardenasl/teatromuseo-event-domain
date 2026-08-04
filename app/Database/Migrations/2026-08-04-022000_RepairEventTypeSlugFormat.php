<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Libraries\Localization\SlugGenerator;
use CodeIgniter\Database\Migration;

/** Rewrites event-type slugs produced by the old iconv-only fallback. */
final class RepairEventTypeSlugFormat extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('event_public_slugs')) {
            return;
        }

        $generator = new SlugGenerator();
        $rows = $this->db->table('event_public_slugs')
            ->where('resource_type', 'event_type')
            ->orderBy('locale', 'ASC')
            ->orderBy('resource_id', 'ASC')
            ->get()->getResultArray();
        $taken = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $resourceId = (int) ($row['resource_id'] ?? 0);
            $locale = strtolower((string) ($row['locale'] ?? ''));
            if ($id < 1 || $resourceId < 1 || $locale === '') {
                continue;
            }

            $translation = $this->db->table('event_translations')
                ->select('value')
                ->where('translatable_type', 'event_type')
                ->where('translatable_id', $resourceId)
                ->where('locale', $locale)
                ->where('field', 'name')
                ->get()->getRowArray();
            $source = (string) ($translation['value'] ?? $row['slug'] ?? '');
            $base = $generator->slugify($source);
            if ($base === '') {
                continue;
            }

            $candidate = $generator->uniquify($base, static function (string $value) use (&$taken, $locale): bool {
                return ! isset($taken[$locale][$value]);
            });
            $taken[$locale][$candidate] = true;

            if ((string) ($row['slug'] ?? '') !== $candidate) {
                $this->db->table('event_public_slugs')->where('id', $id)->update([
                    'slug' => $candidate,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down(): void
    {
        // URL corrections are intentionally not reverted.
    }
}
