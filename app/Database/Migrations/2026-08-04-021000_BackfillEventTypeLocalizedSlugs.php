<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;

final class BackfillEventTypeLocalizedSlugs extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('event_public_slugs')) {
            return;
        }

        $rows = $this->db->table('event_translations')
            ->select('translatable_id, locale, value')
            ->where('translatable_type', 'event_type')
            ->where('field', 'name')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $resourceId = (int) ($row['translatable_id'] ?? 0);
            $locale = strtolower(trim((string) ($row['locale'] ?? '')));
            $name = trim((string) ($row['value'] ?? ''));
            if ($resourceId < 1 || $locale === '' || $name === '') {
                continue;
            }

            $exists = $this->db->table('event_public_slugs')
                ->where('resource_type', 'event_type')
                ->where('resource_id', $resourceId)
                ->where('locale', $locale)
                ->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $slug = $this->slugify($name);
            if ($slug === '') {
                continue;
            }

            $base = $slug;
            $suffix = 2;
            while ($this->db->table('event_public_slugs')
                ->where('resource_type', 'event_type')
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->countAllResults() > 0) {
                $slug = $base . '-' . $suffix++;
            }

            $this->db->table('event_public_slugs')->insert([
                'resource_type' => 'event_type',
                'resource_id' => $resourceId,
                'locale' => $locale,
                'slug' => $slug,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('event_public_slugs')) {
            $this->db->table('event_public_slugs')->where('resource_type', 'event_type')->delete();
        }
    }

    private function slugify(string $value): string
    {
        return (new SlugGenerator())->slugify($value);
    }
}
