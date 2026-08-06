<?php

declare(strict_types=1);

namespace App\Repositories\Events;

use App\Entities\OccurrenceEntity;
use App\Interfaces\Events\OccurrenceRepositoryInterface;
use App\Models\OccurrenceModel;

final class OccurrenceRepository implements OccurrenceRepositoryInterface
{
    public function __construct(private readonly OccurrenceModel $model)
    {
    }

    /**
     * @param list<int> $eventIds
     * @return list<OccurrenceEntity>
     */
    public function findByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        /** @var list<OccurrenceEntity> $occurrences */
        $occurrences = $this->model
            ->select('occurrences.*, venues.name AS venue_name')
            ->join('venues', 'venues.id = occurrences.venue_id', 'left')
            ->whereIn('event_id', $eventIds)
            ->orderBy('start_time', 'ASC')
            ->findAll();

        return $occurrences;
    }
}
