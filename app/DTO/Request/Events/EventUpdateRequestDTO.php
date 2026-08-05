<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventUpdateRequest')]
readonly class EventUpdateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        // Single assignment expression per property (never a second write
        // site in a branch) — PHPStan's readonly analysis only recognizes
        // "assigned exactly once" when there is exactly one textual
        // assignment statement per property, even though map() itself only
        // ever runs once (called from BaseRequestDTO::__construct()).
        $this->uuid = array_key_exists('uuid', $data) && $data['uuid'] !== null ? (string) $data['uuid'] : null;
        $this->title = array_key_exists('title', $data) && $data['title'] !== null ? (string) $data['title'] : null;
        $this->event_type = array_key_exists('event_type', $data) && $data['event_type'] !== null ? (string) $data['event_type'] : null;
        $this->description = array_key_exists('description', $data) && $data['description'] !== null ? (string) $data['description'] : null;
        $this->cover_file_id = array_key_exists('cover_file_id', $data) && $data['cover_file_id'] !== null && $data['cover_file_id'] !== '' ? (int) $data['cover_file_id'] : null;
        $this->gallery_file_ids = array_key_exists('gallery_file_ids', $data) && $data['gallery_file_ids'] !== null ? (string) $data['gallery_file_ids'] : null;
        $this->translations = array_key_exists('translations', $data) && is_array($data['translations']) ? array_values($data['translations']) : null;
        $this->start_time = array_key_exists('start_time', $data) && $data['start_time'] !== null && $data['start_time'] !== '' ? (string) $data['start_time'] : null;
        $this->end_time = array_key_exists('end_time', $data) && $data['end_time'] !== null && $data['end_time'] !== '' ? (string) $data['end_time'] : null;
        $this->venue = array_key_exists('venue', $data) && $data['venue'] !== null && $data['venue'] !== '' ? (string) $data['venue'] : null;
        $this->capacity = array_key_exists('capacity', $data) && $data['capacity'] !== null && $data['capacity'] !== '' ? (int) $data['capacity'] : null;
        $this->available_spots = array_key_exists('available_spots', $data) && $data['available_spots'] !== null && $data['available_spots'] !== '' ? (int) $data['available_spots'] : null;
        $this->status = array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : null;

        // NOT NULL columns (uuid, title, event_type, description, status)
        // are only ever included when a real value was resolved above — a
        // client sending `null` for one of those is treated the same as
        // omitting it, matching the DB constraint. Nullable columns
        // (cover_file_id, gallery_file_ids, start_time, end_time, venue,
        // capacity, available_spots) are included whenever the key was
        // present at all, even when the resolved value is null, so an
        // explicit clear reaches toArray() and actually nulls the column —
        // the bug this fixes is array_filter() silently dropping every
        // null, which made it impossible to ever clear a nullable field.
        $mappedFields = [];
        if ($this->uuid !== null) {
            $mappedFields['uuid'] = $this->uuid;
        }
        if ($this->title !== null) {
            $mappedFields['title'] = $this->title;
        }
        if ($this->event_type !== null) {
            $mappedFields['event_type'] = $this->event_type;
        }
        if ($this->description !== null) {
            $mappedFields['description'] = $this->description;
        }
        if (array_key_exists('cover_file_id', $data)) {
            $mappedFields['cover_file_id'] = $this->cover_file_id;
        }
        if (array_key_exists('gallery_file_ids', $data)) {
            $mappedFields['gallery_file_ids'] = $this->gallery_file_ids;
        }
        if ($this->translations !== null) {
            $mappedFields['translations'] = $this->translations;
        }
        if (array_key_exists('start_time', $data)) {
            $mappedFields['start_time'] = $this->start_time;
        }
        if (array_key_exists('end_time', $data)) {
            $mappedFields['end_time'] = $this->end_time;
        }
        if (array_key_exists('venue', $data)) {
            $mappedFields['venue'] = $this->venue;
        }
        if (array_key_exists('capacity', $data)) {
            $mappedFields['capacity'] = $this->capacity;
        }
        if (array_key_exists('available_spots', $data)) {
            $mappedFields['available_spots'] = $this->available_spots;
        }
        if ($this->status !== null) {
            $mappedFields['status'] = $this->status;
        }

        $this->mappedFields = $mappedFields;

    }

    #[OA\Property(description: 'uuid', type: 'string', nullable: true)]
    public ?string $uuid;
    #[OA\Property(description: 'title', type: 'string', nullable: true)]
    public ?string $title;
    #[OA\Property(description: 'Programming type', type: 'string', enum: ['function', 'festival', 'course', 'workshop', 'other'], nullable: true)]
    public ?string $event_type;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;
    #[OA\Property(description: 'cover_file_id', type: 'integer', nullable: true)]
    public ?int $cover_file_id;
    #[OA\Property(description: 'gallery_file_ids', type: 'string', nullable: true)]
    public ?string $gallery_file_ids;
    /** @var list<array<string, mixed>>|null */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', nullable: true, items: new OA\Items(type: 'object'))]
    public ?array $translations;
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

    /** @var array<string, mixed> */
    private array $mappedFields;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]',
            'title' => 'permit_empty|string|max_length[255]',
            'event_type' => 'permit_empty|string|max_length[80]|is_not_unique[event_types.slug]',
            'description' => 'permit_empty|string',
            'cover_file_id' => 'permit_empty|integer',
            'gallery_file_ids' => 'permit_empty|string',
            'translations' => 'permit_empty',
            'start_time' => 'permit_empty|valid_date',
            'end_time' => 'permit_empty|valid_date',
            'venue' => 'permit_empty|string|max_length[255]',
            'capacity' => 'permit_empty|integer',
            'available_spots' => 'permit_empty|integer',
            'status' => 'permit_empty|string|max_length[255]',
        ];
    }

    /**
     * NOT NULL columns (uuid, title, event_type, description, status) never
     * accept an explicit null here — a client sending `null` for one of
     * those is treated the same as omitting it, matching the DB constraint.
     * Nullable columns (cover_file_id, gallery_file_ids, start_time,
     * end_time, venue, capacity, available_spots) preserve an explicit null
     * so it reaches toArray() and actually clears the column — the bug this
     * fixes is array_filter() silently dropping every null, which made it
     * impossible to ever clear a nullable field via update.
     */
    protected function map(array $data): void
    {
    }

    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
