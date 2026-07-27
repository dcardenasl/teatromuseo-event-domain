<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class MakeLegacyEventScheduleNullable extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE events MODIFY start_time DATETIME NULL');
        $this->db->query('ALTER TABLE events MODIFY end_time DATETIME NULL');
        $this->db->query('ALTER TABLE events MODIFY venue VARCHAR(255) NULL');
        $this->db->query('ALTER TABLE events MODIFY capacity INT UNSIGNED NULL');
        $this->db->query('ALTER TABLE events MODIFY available_spots INT UNSIGNED NULL');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE events MODIFY start_time DATETIME NOT NULL');
        $this->db->query('ALTER TABLE events MODIFY end_time DATETIME NOT NULL');
        $this->db->query('ALTER TABLE events MODIFY venue VARCHAR(255) NOT NULL');
        $this->db->query('ALTER TABLE events MODIFY capacity INT UNSIGNED NOT NULL');
        $this->db->query('ALTER TABLE events MODIFY available_spots INT UNSIGNED NOT NULL');
    }
}
