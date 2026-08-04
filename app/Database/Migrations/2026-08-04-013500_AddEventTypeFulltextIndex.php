<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddEventTypeFulltextIndex extends Migration
{
    private const INDEX_NAME = 'ft_event_types_search';

    public function up(): void
    {
        if (! in_array(strtolower((string) $this->db->DBDriver), ['mysqli', 'mysql'], true)) {
            return;
        }

        $this->db->query(sprintf(
            'ALTER TABLE `event_types` ADD FULLTEXT KEY `%s` (`slug`, `name`)',
            self::INDEX_NAME,
        ));
    }

    public function down(): void
    {
        if (! in_array(strtolower((string) $this->db->DBDriver), ['mysqli', 'mysql'], true)) {
            return;
        }

        $this->db->query(sprintf(
            'ALTER TABLE `event_types` DROP INDEX `%s`',
            self::INDEX_NAME,
        ));
    }
}
