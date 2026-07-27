<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventUpdateRequest')]
readonly class EventUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'uuid', type: 'string', nullable: true)]
    public ?string $uuid;
    #[OA\Property(description: 'title', type: 'string', nullable: true)]
    public ?string $title;
    #[OA\Property(description: 'Programming type', type: 'string', enum: ['function', 'festival', 'course', 'workshop', 'other'], nullable: true)]
    public ?string $event_type;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;
    #[OA\Property(description: 'start_time', type: 'string', format: 'date-time', nullable: true)]
    public ?string $start_time;
    #[OA\Property(description: 'end_time', type: 'string', format: 'date-time', nullable: true)]
    public ?string $end_time;
    #[OA\Property(description: 'venue', type: 'string', nullable: true)]
    public ?string $venue;
    #[OA\Property(description: 'capacity', type: 'integer', nullable: true)]
    public ?int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer', nullable: true)]
    public ?int $available_spots;
    #[OA\Property(description: 'status', type: 'string', nullable: true)]
    public ?string $status;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]',
            'title' => 'permit_empty|string|max_length[255]',
            'event_type' => 'permit_empty|in_list[function,festival,course,workshop,other]',
            'description' => 'permit_empty|string',
            'start_time' => 'permit_empty|valid_date',
            'end_time' => 'permit_empty|valid_date',
            'venue' => 'permit_empty|string|max_length[255]',
            'capacity' => 'permit_empty|integer',
            'available_spots' => 'permit_empty|integer',
            'status' => 'permit_empty|string|max_length[255]',
        ];
    }

    protected function map(array $data): void
    {
        $this->uuid = $data['uuid'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->event_type = $data['event_type'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->start_time = $data['start_time'] ?? null;
        $this->end_time = $data['end_time'] ?? null;
        $this->venue = $data['venue'] ?? null;
        $this->capacity = isset($data['capacity']) ? (int) $data['capacity'] : null;
        $this->available_spots = isset($data['available_spots']) ? (int) $data['available_spots'] : null;
        $this->status = $data['status'] ?? null;
    }

    public function toArray(): array
    {
        return array_filter([
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
        ], fn ($v) => $v !== null);
    }
}
