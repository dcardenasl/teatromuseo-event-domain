<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Public routing slugs for Event Domain resources, one per locale.
 *
 * Slugs live in a dedicated table (not in the event_translations EAV rows)
 * because they are routing identifiers with hard uniqueness requirements the
 * EAV shape cannot enforce: the composite unique keys below guarantee both
 * "one slug per resource+locale" and "a slug is unique within its locale".
 * Locale codes follow the same BCP-47-like contract as event_translations —
 * the CMS owns the language catalog, this domain stays locale-agnostic.
 */
class CreateEventPublicSlugsTable extends Migration
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
            'resource_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'resource_id' => [
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
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => false,
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
        $this->forge->addUniqueKey(
            ['resource_type', 'locale', 'slug'],
            'uq_event_public_slugs_locale_slug'
        );
        $this->forge->addUniqueKey(
            ['resource_type', 'resource_id', 'locale'],
            'uq_event_public_slugs_resource_locale'
        );
        $this->forge->createTable('event_public_slugs');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_public_slugs');
    }
}
