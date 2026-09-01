<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;

/**
 * Generates the initial routing slug per locale for pre-existing events:
 * one row per event_translations title, plus the legacy fallback locale
 * projected from events.title when no translation covers it.
 */
class BackfillEventPublicSlugs extends Migration
{
    public function up(): void
    {
        $generator = new SlugGenerator();
        $legacyLocale = config('Localization')->legacyFallbackLocale;
        $now = date('Y-m-d H:i:s');

        /** @var array<string, true> $taken "locale|slug" of every slug already assigned */
        $taken = [];
        foreach ($this->db->table('event_public_slugs')->where('resource_type', 'event')->get()->getResultArray() as $row) {
            $taken[$row['locale'] . '|' . $row['slug']] = true;
        }

        $events = $this->db->table('events')->select('id, title')->get()->getResultArray();

        foreach ($events as $event) {
            $eventId = (int) ($event['id'] ?? 0);
            if ($eventId < 1) {
                continue;
            }

            $titlesByLocale = [];

            $translationRows = $this->db->table('event_translations')
                ->where(['translatable_type' => 'event', 'translatable_id' => $eventId, 'field' => 'title'])
                ->get()
                ->getResultArray();
            foreach ($translationRows as $row) {
                $title = trim((string) ($row['value'] ?? ''));
                if ($title !== '') {
                    $titlesByLocale[(string) $row['locale']] = $title;
                }
            }

            $legacyTitle = trim((string) ($event['title'] ?? ''));
            if (! isset($titlesByLocale[$legacyLocale]) && $legacyTitle !== '') {
                $titlesByLocale[$legacyLocale] = $legacyTitle;
            }

            foreach ($titlesByLocale as $locale => $title) {
                $exists = $this->db->table('event_public_slugs')
                    ->where(['resource_type' => 'event', 'resource_id' => $eventId, 'locale' => $locale])
                    ->countAllResults() > 0;
                if ($exists) {
                    continue;
                }

                $slug = $generator->slugify($title);
                if ($slug === '') {
                    continue;
                }

                $slug = $generator->uniquify(
                    $slug,
                    static fn (string $candidate): bool => ! isset($taken[$locale . '|' . $candidate])
                );
                $taken[$locale . '|' . $slug] = true;

                $this->db->table('event_public_slugs')->insert([
                    'resource_type' => 'event',
                    'resource_id'   => $eventId,
                    'locale'        => $locale,
                    'slug'          => $slug,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Slug rows are dropped together with the table by the schema
        // migration's down(); nothing to restore here.
    }
}
