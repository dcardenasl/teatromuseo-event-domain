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
    #[OA\Property(description: 'cover_file_id', type: 'integer', nullable: true)]
    public ?int $cover_file_id;
    #[OA\Property(description: 'gallery_file_ids', type: 'string', nullable: true)]
    public ?string $gallery_file_ids;
    /** @var list<array<string, mixed>> */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;
    #[OA\Property(description: 'start_time', type: 'string', format: 'date-time')]
    public ?string $start_time;
    #[OA\Property(description: 'end_time', type: 'string', format: 'date-time')]
    public ?string $end_time;
    #[OA\Property(description: 'venue', type: 'string')]
    public ?string $venue;
    #[OA\Property(description: 'capacity', type: 'integer')]
    public ?int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer')]
    public ?int $available_spots;
    #[OA\Property(description: 'status', type: 'string')]
    public string $status;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]|is_unique[events.uuid]',
            'title' => 'required|string|max_length[255]',
            'event_type' => 'required|in_list[function,festival,course,workshop,other]',
            'description' => 'required|string',
            'cover_file_id' => 'permit_empty|integer',
            'gallery_file_ids' => 'permit_empty|string',
            'translations' => 'permit_empty',
            'start_time' => 'permit_empty|valid_date',
            'end_time' => 'permit_empty|valid_date',
            'venue' => 'permit_empty|string|max_length[255]',
            'capacity' => 'permit_empty|integer',
            'available_spots' => 'permit_empty|integer',
            'status' => 'required|string|max_length[255]',
        ];
    }

    protected function map(array $data): void
    {
        $this->uuid = (string) ($data['uuid'] ?? '');
        $this->title = (string) ($data['title'] ?? '');
        $this->event_type = (string) ($data['event_type'] ?? 'function');
        $this->description = (string) ($data['description'] ?? '');
        $this->cover_file_id = isset($data['cover_file_id']) ? (int) $data['cover_file_id'] : null;
        $this->gallery_file_ids = $data['gallery_file_ids'] ?? null;
        $this->translations = is_array($data['translations'] ?? null) ? array_values($data['translations']) : [];
        $this->start_time = isset($data['start_time']) ? (string) $data['start_time'] : null;
        $this->end_time = isset($data['end_time']) ? (string) $data['end_time'] : null;
        $this->venue = isset($data['venue']) ? (string) $data['venue'] : null;
        $this->capacity = isset($data['capacity']) ? (int) $data['capacity'] : null;
        $this->available_spots = isset($data['available_spots']) ? (int) $data['available_spots'] : null;
        $this->status = (string) ($data['status'] ?? '');
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'event_type' => $this->event_type,
            'description' => $this->description,
            'cover_file_id' => $this->cover_file_id,
            'gallery_file_ids' => $this->gallery_file_ids,
            'translations' => $this->translations,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'venue' => $this->venue,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'status' => $this->status,
        ];
    }
}
