<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EventResponse',
    title: 'Event Response',
    required: ["id","uuid","title","event_type","description","start_time","end_time","venue","capacity","available_spots","status"]
)]
final readonly class EventResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'uuid', type: 'string')]
        public string $uuid,
        #[OA\Property(description: 'title', type: 'string')]
        public string $title,
        #[OA\Property(description: 'Programming type', type: 'string')]
        public string $event_type,
        #[OA\Property(description: 'description', type: 'string')]
        public string $description,
        #[OA\Property(description: 'start_time', type: 'string', format: 'date-time')]
        public string $start_time,
        #[OA\Property(description: 'end_time', type: 'string', format: 'date-time')]
        public string $end_time,
        #[OA\Property(description: 'venue', type: 'string')]
        public string $venue,
        #[OA\Property(description: 'capacity', type: 'integer')]
        public int $capacity,
        #[OA\Property(description: 'available_spots', type: 'integer')]
        public int $available_spots,
        #[OA\Property(description: 'status', type: 'string')]
        public string $status,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            uuid: (string) ($data['uuid'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            event_type: (string) ($data['event_type'] ?? 'function'),
            description: (string) ($data['description'] ?? ''),
            start_time: (string) ($data['start_time'] ?? ''),
            end_time: (string) ($data['end_time'] ?? ''),
            venue: (string) ($data['venue'] ?? ''),
            capacity: (int) ($data['capacity'] ?? 0),
            available_spots: (int) ($data['available_spots'] ?? 0),
            status: (string) ($data['status'] ?? ''),
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'event_type' => $this->event_type,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'venue' => $this->venue,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
