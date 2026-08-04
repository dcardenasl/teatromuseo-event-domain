<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Libraries\Localization\SlugGenerator;
use CodeIgniter\Database\Migration;

final class EnsureEventTypeLocalizedCatalog extends Migration
{
    public function up(): void
    {
        $types = $this->db->table('event_types')->select('id, slug, name')->get()->getResultArray();
        $now = date('Y-m-d H:i:s');
        $labels = [
            'function' => ['es' => 'Función', 'en' => 'Function', 'fr' => 'Fonction', 'pt' => 'Função'],
            'festival' => ['es' => 'Festival', 'en' => 'Festival', 'fr' => 'Festival', 'pt' => 'Festival'],
            'course' => ['es' => 'TeatroEscuela', 'en' => 'TeatroEscuela', 'fr' => 'TeatroEscuela', 'pt' => 'TeatroEscuela'],
            'workshop' => ['es' => 'Taller', 'en' => 'Workshop', 'fr' => 'Atelier', 'pt' => 'Oficina'],
            'other' => ['es' => 'Actividad', 'en' => 'Activity', 'fr' => 'Activité', 'pt' => 'Atividade'],
        ];

        foreach ($types as $type) {
            $id = (int) ($type['id'] ?? 0);
            $code = (string) ($type['slug'] ?? '');
            if ($id < 1) {
                continue;
            }

            $localeLabels = $labels[$code] ?? ['es' => (string) ($type['name'] ?? '')];
            foreach ($localeLabels as $locale => $name) {
                $exists = $this->db->table('event_translations')
                    ->where('translatable_type', 'event_type')
                    ->where('translatable_id', $id)
                    ->where('locale', $locale)
                    ->where('field', 'name')
                    ->countAllResults();
                if ($exists === 0 && $name !== '') {
                    $this->db->table('event_translations')->insert([
                        'translatable_type' => 'event_type',
                        'translatable_id' => $id,
                        'locale' => $locale,
                        'field' => 'name',
                        'value' => $name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // The preceding migration may have run before the legacy catalog was
        // present, so repeat the slug projection after guaranteeing names.
        foreach ($this->db->table('event_translations')
            ->select('translatable_id, locale, value')
            ->where('translatable_type', 'event_type')
            ->where('field', 'name')
            ->get()->getResultArray() as $row) {
            $id = (int) ($row['translatable_id'] ?? 0);
            $locale = strtolower(trim((string) ($row['locale'] ?? '')));
            $slug = $this->slugify((string) ($row['value'] ?? ''));
            if ($id < 1 || $locale === '' || $slug === '') {
                continue;
            }
            if ($this->db->table('event_public_slugs')->where([
                'resource_type' => 'event_type', 'resource_id' => $id, 'locale' => $locale,
            ])->countAllResults() > 0) {
                continue;
            }
            $this->db->table('event_public_slugs')->insert([
                'resource_type' => 'event_type', 'resource_id' => $id,
                'locale' => $locale, 'slug' => $slug,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Data seeded by this repair is intentionally retained for URL stability.
    }

    private function slugify(string $value): string
    {
        return (new SlugGenerator())->slugify($value);
    }
}
