<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventCreateRequest')]
readonly class EventCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'uuid', type: 'string')]
    public string $uuid;
    #[OA\Property(description: 'title', type: 'string')]
    public string $title;
    #[OA\Property(description: 'Programming type', type: 'string', enum: ['function', 'festival', 'course', 'workshop', 'other'])]
    public string $event_type;
    #[OA\Property(description: 'description', type: 'string')]
    public string $description;
    #[OA\Property(description: 'start_time', type: 'string', format: 'date-time')]
    public string $start_time;
    #[OA\Property(description: 'end_time', type: 'string', format: 'date-time')]
    public string $end_time;
    #[OA\Property(description: 'venue', type: 'string')]
    public string $venue;
    #[OA\Property(description: 'capacity', type: 'integer')]
    public int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer')]
    public int $available_spots;
    #[OA\Property(description: 'status', type: 'string')]
    public string $status;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]|is_unique[events.uuid]',
            'title' => 'required|string|max_length[255]',
            'event_type' => 'required|in_list[function,festival,course,workshop,other]',
            'description' => 'required|string',
            'start_time' => 'required|valid_date',
            'end_time' => 'required|valid_date',
            'venue' => 'required|string|max_length[255]',
            'capacity' => 'required|integer',
            'available_spots' => 'required|integer',
            'status' => 'required|string|max_length[255]',
        ];
    }

    protected function map(array $data): void
    {
        $this->uuid = (string) ($data['uuid'] ?? '');
        $this->title = (string) ($data['title'] ?? '');
        $this->event_type = (string) ($data['event_type'] ?? 'function');
        $this->description = (string) ($data['description'] ?? '');
        $this->start_time = (string) ($data['start_time'] ?? '');
        $this->end_time = (string) ($data['end_time'] ?? '');
        $this->venue = (string) ($data['venue'] ?? '');
        $this->capacity = (int) ($data['capacity'] ?? 0);
        $this->available_spots = (int) ($data['available_spots'] ?? 0);
        $this->status = (string) ($data['status'] ?? '');
    }

    public function toArray(): array
    {
        return [
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
        ];
    }
}
