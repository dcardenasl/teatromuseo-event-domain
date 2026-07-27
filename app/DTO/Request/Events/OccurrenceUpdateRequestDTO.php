<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'OccurrenceUpdateRequest')]
readonly class OccurrenceUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'event_id', type: 'integer', nullable: true)]
    public ?int $event_id;
    #[OA\Property(description: 'venue_id', type: 'integer', nullable: true)]
    public ?int $venue_id;
    #[OA\Property(description: 'start_time', type: 'string', format: 'date-time', nullable: true)]
    public ?string $start_time;
    #[OA\Property(description: 'end_time', type: 'string', format: 'date-time', nullable: true)]
    public ?string $end_time;
    #[OA\Property(description: 'status', type: 'string', nullable: true)]
    public ?string $status;
    #[OA\Property(description: 'capacity', type: 'integer', nullable: true)]
    public ?int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer', nullable: true)]
    public ?int $available_spots;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'permit_empty|is_natural_no_zero|is_not_unique[events.id]',
            'venue_id' => 'permit_empty|is_natural_no_zero|is_not_unique[venues.id]',
            'start_time' => 'permit_empty|valid_date',
            'end_time' => 'permit_empty|valid_date',
            'status' => 'permit_empty|string|max_length[255]',
            'capacity' => 'permit_empty|integer',
            'available_spots' => 'permit_empty|integer',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->event_id = isset($data['event_id']) ? (int) $data['event_id'] : null;
        $this->venue_id = isset($data['venue_id']) ? (int) $data['venue_id'] : null;
        $this->start_time = $data['start_time'] ?? null;
        $this->end_time = $data['end_time'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->capacity = isset($data['capacity']) ? (int) $data['capacity'] : null;
        $this->available_spots = isset($data['available_spots']) ? (int) $data['available_spots'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'event_id' => $this->event_id,
            'venue_id' => $this->venue_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
