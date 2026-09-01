<?php

declare(strict_types=1);

namespace App\Repositories\Events;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

abstract class AbstractAdminListProjectionRepository
{
    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(
        protected readonly Model $model,
        protected readonly BaseConnection $db,
    ) {
    }

    /** @param array<string, mixed> $criteria */
    protected function criteriaValue(array $criteria, string $key): mixed
    {
        $filter = $criteria['filter'] ?? null;
        if (is_array($filter) && array_key_exists($key, $filter)) {
            return $filter[$key];
        }

        return $criteria[$key] ?? null;
    }

    /**
     * @param array<int|string, mixed> $binds
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int, from: int, to: int}
     */
    protected function execute(string $sql, array $binds, int $page, int $perPage, string $error): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $this->configureProjectionTransport();
        $query = $this->db->query($sql, $binds);
        if (! $query instanceof ResultInterface) {
            throw new \RuntimeException($error);
        }
        /** @var list<array<string, mixed>> $rows */
        $rows = $query->getResultArray();
        $total = $rows !== [] ? (int) ($rows[0]['total_items'] ?? 0) : 0;

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            'from' => $rows === [] ? 0 : $offset + 1,
            'to' => $rows === [] ? 0 : $offset + count($rows),
        ];
    }

    private function configureProjectionTransport(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $this->db->query('SET SESSION group_concat_max_len = 1048576');
    }
}
