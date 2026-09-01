<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stores localized content for Event Domain resources.
 *
 * The CMS owns the language catalog, but this domain deliberately stores the
 * locale code instead of a CMS language id. Domains have independent
 * databases, and a BCP-47-like code is the stable cross-domain contract.
 */
class CreateEventTranslationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'translatable_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'translatable_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'locale' => [
                'type'       => 'VARCHAR',
                'constraint' => 35,
                'null'       => false,
            ],
            'field' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'value' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey([
            'translatable_type',
            'translatable_id',
            'locale',
            'field',
        ], 'uq_event_translations_resource_locale_field');
        $this->forge->addKey([
            'translatable_type',
            'translatable_id',
            'locale',
        ]);
        $this->forge->createTable('event_translations');

        // Existing records only have the legacy projection. Preserve it as
        // the configured legacy fallback so old data remains translatable
        // without requiring a content migration before the schema migration.
        $events = $this->db->table('events')
            ->select('id, title, description')
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        if ($events === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $legacyLocale = config('Localization')->legacyFallbackLocale;
        $rows = [];
        foreach ($events as $event) {
            $resourceId = (int) ($event['id'] ?? 0);
            if ($resourceId < 1) {
                continue;
            }

            foreach (['title', 'description'] as $field) {
                $value = (string) ($event[$field] ?? '');
                if ($value === '') {
                    continue;
                }

                $rows[] = [
                    'translatable_type' => 'event',
                    'translatable_id'   => $resourceId,
                    'locale'            => $legacyLocale,
                    'field'             => $field,
                    'value'             => $value,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }
        }

        if ($rows !== []) {
            $this->db->table('event_translations')->insertBatch($rows);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('event_translations');
    }
}
