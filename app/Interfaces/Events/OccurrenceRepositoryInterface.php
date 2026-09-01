<?php

declare(strict_types=1);

namespace App\Interfaces\Events;

use App\Entities\OccurrenceEntity;

interface OccurrenceRepositoryInterface
{
    /**
     * @param list<int> $eventIds
     * @return list<OccurrenceEntity>
     */
    public function findByEventIds(array $eventIds): array;
}
