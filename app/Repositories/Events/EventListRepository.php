<?php

declare(strict_types=1);

namespace App\Repositories\Events;

use App\Interfaces\Events\AdminListProjectionRepositoryInterface;
use App\Models\EventModel;

final class EventListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    /** @param \CodeIgniter\Database\BaseConnection<mixed, mixed> $db */
    public function __construct(EventModel $model, \CodeIgniter\Database\BaseConnection $db)
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
        $where = ['e.deleted_at IS NULL'];
        $binds = [];
        foreach (['status', 'event_type'] as $filter) {
            $value = $this->criteriaValue($criteria, $filter);
            if (is_string($value) && $value !== '') {
                $where[] = 'e.' . $filter . ' = ?';
                $binds[] = $value;
            }
        }
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    e.title LIKE ? OR e.description LIKE ? OR e.event_type LIKE ? OR EXISTS (
        SELECT 1 FROM event_translations et_search
        WHERE et_search.translatable_type = 'event'
          AND et_search.translatable_id = e.id
          AND et_search.value LIKE ?
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle, $needle);
        }
        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sorts = [
            'title' => ['e.title ASC, e.id ASC', 'p.title ASC, p.id ASC'],
            '-title' => ['e.title DESC, e.id DESC', 'p.title DESC, p.id DESC'],
            'event_type' => ['e.event_type ASC, e.id ASC', 'p.event_type ASC, p.id ASC'],
            '-event_type' => ['e.event_type DESC, e.id DESC', 'p.event_type DESC, p.id DESC'],
            'status' => ['e.status ASC, e.id ASC', 'p.status ASC, p.id ASC'],
            '-status' => ['e.status DESC, e.id DESC', 'p.status DESC, p.id DESC'],
            'created_at' => ['e.created_at ASC, e.id ASC', 'p.created_at ASC, p.id ASC'],
            '-created_at' => ['e.created_at DESC, e.id DESC', 'p.created_at DESC, p.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sorts[$sort] ?? $sorts['-created_at'];
        $whereSql = implode("\n      AND ", $where);
        $sql = <<<SQL
WITH filtered_events AS (
    SELECT e.id, e.uuid, e.title, e.event_type, e.description, e.cover_file_id, e.gallery_file_ids, e.status, e.created_at, e.updated_at,
           COUNT(*) OVER () AS total_items
    FROM events e
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT p.*, GROUP_CONCAT(DISTINCT CONCAT(et.locale, ':', et.field, ':', HEX(et.value)) ORDER BY et.locale, et.field SEPARATOR '|') AS translations_data,
       GROUP_CONCAT(DISTINCT CONCAT(eps.locale, ':', HEX(eps.slug)) ORDER BY eps.locale SEPARATOR '|') AS slugs_data,
       MAX(p.total_items) AS total_items
FROM filtered_events p
LEFT JOIN event_translations et ON et.translatable_type = 'event' AND et.translatable_id = p.id AND et.field IN ('title', 'description')
LEFT JOIN event_public_slugs eps ON eps.resource_type = 'event' AND eps.resource_id = p.id
GROUP BY p.id, p.uuid, p.title, p.event_type, p.description, p.cover_file_id, p.gallery_file_ids, p.status, p.created_at, p.updated_at
ORDER BY {$outerOrder}
SQL;
        return $this->execute($sql, $binds, $page, $perPage, 'Unable to execute the event list projection.');
    }
}
