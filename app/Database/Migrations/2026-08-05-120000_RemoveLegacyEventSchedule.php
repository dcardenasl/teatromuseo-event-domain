<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Removes schedule fields from events after occurrences became canonical. */
final class RemoveLegacyEventSchedule extends Migration
{
    private const COLUMNS = ['start_time', 'end_time', 'venue', 'capacity', 'available_spots'];

    public function up(): void
    {
        $columns = array_values(array_filter(
            self::COLUMNS,
            fn (string $column): bool => $this->db->fieldExists($column, 'events')
        ));

        if ($columns !== []) {
            $this->forge->dropColumn('events', $columns);
        }
    }

    public function down(): void
    {
        $this->forge->addColumn('events', [
            'start_time' => ['type' => 'DATETIME', 'null' => true],
            'end_time' => ['type' => 'DATETIME', 'null' => true],
            'venue' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'capacity' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'available_spots' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }
}
