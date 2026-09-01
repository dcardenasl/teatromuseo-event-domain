<?php

declare(strict_types=1);

namespace App\Repositories\Events;

use App\Interfaces\Events\AdminListProjectionRepositoryInterface;
use App\Models\EventTypeModel;

final class EventTypeListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    /** @param \CodeIgniter\Database\BaseConnection<mixed, mixed> $db */
    public function __construct(EventTypeModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int, from: int, to: int}
     */
    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['et.deleted_at IS NULL'];
        $binds = [];
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    et.slug LIKE ? OR et.name LIKE ? OR EXISTS (
        SELECT 1 FROM event_translations ett_search
        WHERE ett_search.translatable_type = 'event_type'
          AND ett_search.translatable_id = et.id
          AND ett_search.value LIKE ?
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle);
        }
        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sorts = [
            'name' => ['et.name ASC, et.id ASC', 'p.name ASC, p.id ASC'],
            '-name' => ['et.name DESC, et.id DESC', 'p.name DESC, p.id DESC'],
            'is_active' => ['et.is_active ASC, et.id ASC', 'p.is_active ASC, p.id ASC'],
            '-is_active' => ['et.is_active DESC, et.id DESC', 'p.is_active DESC, p.id DESC'],
            'created_at' => ['et.created_at ASC, et.id ASC', 'p.created_at ASC, p.id ASC'],
            '-created_at' => ['et.created_at DESC, et.id DESC', 'p.created_at DESC, p.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sorts[$sort] ?? $sorts['-created_at'];
        $whereSql = implode("\n      AND ", $where);
        $sql = <<<SQL
WITH filtered_event_types AS (
    SELECT et.id, et.slug, et.name, et.sort_order, et.is_active, et.created_at, et.updated_at,
           COUNT(*) OVER () AS total_items
    FROM event_types et
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT p.*, GROUP_CONCAT(DISTINCT CONCAT(ett.locale, ':', ett.field, ':', HEX(ett.value)) ORDER BY ett.locale, ett.field SEPARATOR '|') AS translations_data,
       GROUP_CONCAT(DISTINCT CONCAT(eps.locale, ':', HEX(eps.slug)) ORDER BY eps.locale SEPARATOR '|') AS slugs_data,
       MAX(p.total_items) AS total_items
FROM filtered_event_types p
LEFT JOIN event_translations ett ON ett.translatable_type = 'event_type' AND ett.translatable_id = p.id AND ett.field = 'name'
LEFT JOIN event_public_slugs eps ON eps.resource_type = 'event_type' AND eps.resource_id = p.id
GROUP BY p.id, p.slug, p.name, p.sort_order, p.is_active, p.created_at, p.updated_at
ORDER BY {$outerOrder}
SQL;
        return $this->execute($sql, $binds, $page, $perPage, 'Unable to execute the event type list projection.');
    }
}
