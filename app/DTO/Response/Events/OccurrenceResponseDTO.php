<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use App\Traits\DTO\NormalizesResponseTimestamps;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OccurrenceResponse',
    title: 'Occurrence Response',
    required: ["id","event_id","start_time","end_time","status","capacity","available_spots"]
)]
final readonly class OccurrenceResponseDTO implements DataTransferObjectInterface
{
    use NormalizesResponseTimestamps;

    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'event_id', type: 'integer')]
        public int $event_id,
        #[OA\Property(description: 'venue_id', type: 'integer', nullable: true)]
        public ?int $venue_id,
        #[OA\Property(description: 'start_time', type: 'string', format: 'date-time')]
        public string $start_time,
        #[OA\Property(description: 'end_time', type: 'string', format: 'date-time')]
        public string $end_time,
        #[OA\Property(description: 'status', type: 'string')]
        public string $status,
        #[OA\Property(description: 'capacity', type: 'integer')]
        public int $capacity,
        #[OA\Property(description: 'available_spots', type: 'integer')]
        public int $available_spots,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            event_id: (int) ($data['event_id'] ?? 0),
            venue_id: isset($data['venue_id']) ? (int) $data['venue_id'] : null,
            start_time: (string) ($data['start_time'] ?? ''),
            end_time: (string) ($data['end_time'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            capacity: (int) ($data['capacity'] ?? 0),
            available_spots: (int) ($data['available_spots'] ?? 0),
            createdAt: self::normalizeResponseTimestamp($data['created_at'] ?? null),
            updatedAt: self::normalizeResponseTimestamp($data['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'venue_id' => $this->venue_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
