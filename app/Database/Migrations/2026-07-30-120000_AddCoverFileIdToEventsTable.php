<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddCoverFileIdToEventsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('events', [
            'cover_file_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'description',
            ],
            'gallery_file_ids' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'cover_file_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('events', ['cover_file_id', 'gallery_file_ids']);
    }
}
