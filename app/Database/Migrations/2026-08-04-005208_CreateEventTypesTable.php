<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateEventTypesTable extends Migration
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
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'sort_order' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'is_active' => [
                'type' => 'TINYINT',
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('sort_order');
        $this->forge->addKey('is_active');
        $this->forge->createTable('event_types');

        $this->db->table('event_types')->insertBatch([
            ['slug' => 'function', 'name' => 'Función', 'sort_order' => 10, 'is_active' => 1],
            ['slug' => 'festival', 'name' => 'Festival', 'sort_order' => 20, 'is_active' => 1],
            ['slug' => 'course', 'name' => 'TeatroEscuela', 'sort_order' => 30, 'is_active' => 1],
            ['slug' => 'workshop', 'name' => 'Taller', 'sort_order' => 40, 'is_active' => 1],
            ['slug' => 'other', 'name' => 'Actividad', 'sort_order' => 50, 'is_active' => 1],
        ]);

        // Keep the existing API contract (events.event_type is a slug) while
        // making the value referentially valid and administrable.
        $this->forge->modifyColumn('events', [
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
                'default'    => 'function',
            ],
        ]);
        $this->forge->addForeignKey('event_type', 'event_types', 'slug', 'RESTRICT', 'CASCADE', 'fk_events_event_type');

        $this->seedTranslations();
    }

    public function down(): void
    {
        $foreignKeys = $this->db->query(
            'SELECT DISTINCT kcu.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE kcu
             WHERE kcu.TABLE_SCHEMA = DATABASE()
               AND kcu.TABLE_NAME = ?
               AND kcu.COLUMN_NAME = ?
               AND kcu.REFERENCED_TABLE_NAME = ?',
            ['events', 'event_type', 'event_types']
        )->getResultArray();

        foreach ($foreignKeys as $foreignKey) {
            $name = (string) ($foreignKey['CONSTRAINT_NAME'] ?? '');
            if ($name !== '') {
                $this->forge->dropForeignKey('events', $name);
            }
        }

        $this->forge->modifyColumn('events', [
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => false,
                'default'    => 'function',
            ],
        ]);
        $this->forge->dropTable('event_types');
    }

    private function seedTranslations(): void
    {
        if (! $this->db->tableExists('event_translations')) {
            return;
        }

        $ids = $this->db->table('event_types')->select('id, slug')->get()->getResultArray();
        $names = [
            'function' => ['es' => 'Función', 'en' => 'Function'],
            'festival' => ['es' => 'Festival', 'en' => 'Festival'],
            'course' => ['es' => 'TeatroEscuela', 'en' => 'TeatroEscuela'],
            'workshop' => ['es' => 'Taller', 'en' => 'Workshop'],
            'other' => ['es' => 'Actividad', 'en' => 'Activity'],
        ];
        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach ($ids as $row) {
            foreach ($names[(string) $row['slug']] ?? [] as $locale => $name) {
                $rows[] = [
                    'translatable_type' => 'event_type',
                    'translatable_id' => (int) $row['id'],
                    'locale' => $locale,
                    'field' => 'name',
                    'value' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            $this->db->table('event_translations')->insertBatch($rows);
        }
    }
}
