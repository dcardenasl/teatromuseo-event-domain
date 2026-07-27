<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddEventTypeToEventsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('events', [
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'default'    => 'function',
                'null'       => false,
                'after'      => 'title',
            ],
        ]);

        $this->forge->addKey('event_type');
    }

    public function down(): void
    {
        $this->forge->dropColumn('events', 'event_type');
    }
}
