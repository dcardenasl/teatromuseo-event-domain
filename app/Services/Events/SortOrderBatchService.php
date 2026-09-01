<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\DTO\Request\Events\SortOrderBatchRequestDTO;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use RuntimeException;

/** Atomically reorders event types in one domain request. */
final class SortOrderBatchService
{
    /** @var array<string, array{table: string, cache: list<string>}> */
    private const RESOURCES = [
        'event_types' => ['table' => 'event_types', 'cache' => ['event_types', 'events']],
    ];

    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(
        private BaseConnection $db,
        private PublicCacheInvalidationNotifierInterface $cacheInvalidator,
    ) {
    }

    /** @return array{updated: int} */
    public function reorder(SortOrderBatchRequestDTO $request): array
    {
        $configuration = self::RESOURCES[$request->resource] ?? null;
        if ($configuration === null) {
            throw new ValidationException(lang('Api.invalidRequest'));
        }

        $ids = array_map(static fn (array $item): int => $item['id'], $request->items);
        $this->db->transStart();

        $existingResult = $this->db
            ->table($configuration['table'])
            ->select('id')
            ->whereIn('id', $ids)
            ->where('deleted_at', null)
            ->get();
        if ($existingResult === false) {
            $this->db->transRollback();
            throw new RuntimeException(lang('Api.transactionFailed'));
        }

        $existingRows = $existingResult->getResultArray();

        if (count($existingRows) !== count($ids)) {
            $this->db->transRollback();
            throw new ValidationException(lang('Api.invalidRequest'));
        }

        $caseFragments = [];
        $bindings = [];
        foreach ($request->items as $item) {
            $caseFragments[] = 'WHEN ? THEN ?';
            $bindings[] = $item['id'];
            $bindings[] = $item['sort_order'];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = sprintf(
            'UPDATE `%s` SET `sort_order` = CASE `id` %s ELSE `sort_order` END WHERE `id` IN (%s) AND `deleted_at` IS NULL',
            $configuration['table'],
            implode(' ', $caseFragments),
            $placeholders,
        );
        $bindings = [...$bindings, ...$ids];

        if (! $this->db->query($sql, $bindings)) {
            $this->db->transRollback();
            throw new RuntimeException(lang('Api.transactionFailed'));
        }

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new RuntimeException(lang('Api.transactionFailed'));
        }

        $this->cacheInvalidator->invalidate($configuration['cache']);

        return ['updated' => count($ids)];
    }
}
