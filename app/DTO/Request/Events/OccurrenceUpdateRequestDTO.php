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

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * venue_id is the only nullable column on occurrences — it preserves an
     * explicit null so it reaches toArray() and actually clears the column
     * (e.g. detaching an occurrence from its venue). Every other field is
     * NOT NULL, so an explicit null there is treated the same as omitting
     * the field — the bug this fixes is array_filter() silently dropping
     * every null, which made it impossible to ever clear the one field
     * that can be.
     *
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->event_id = array_key_exists('event_id', $data) && $data['event_id'] !== null && $data['event_id'] !== '' ? (int) $data['event_id'] : null;
        $this->venue_id = array_key_exists('venue_id', $data) && $data['venue_id'] !== null && $data['venue_id'] !== '' ? (int) $data['venue_id'] : null;
        $this->start_time = array_key_exists('start_time', $data) && $data['start_time'] !== null ? (string) $data['start_time'] : null;
        $this->end_time = array_key_exists('end_time', $data) && $data['end_time'] !== null ? (string) $data['end_time'] : null;
        $this->status = array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : null;
        $this->capacity = array_key_exists('capacity', $data) && $data['capacity'] !== null && $data['capacity'] !== '' ? (int) $data['capacity'] : null;
        $this->available_spots = array_key_exists('available_spots', $data) && $data['available_spots'] !== null && $data['available_spots'] !== '' ? (int) $data['available_spots'] : null;

        $mappedFields = [];
        if ($this->event_id !== null) {
            $mappedFields['event_id'] = $this->event_id;
        }
        if (array_key_exists('venue_id', $data)) {
            $mappedFields['venue_id'] = $this->venue_id;
        }
        if ($this->start_time !== null) {
            $mappedFields['start_time'] = $this->start_time;
        }
        if ($this->end_time !== null) {
            $mappedFields['end_time'] = $this->end_time;
        }
        if ($this->status !== null) {
            $mappedFields['status'] = $this->status;
        }
        if ($this->capacity !== null) {
            $mappedFields['capacity'] = $this->capacity;
        }
        if ($this->available_spots !== null) {
            $mappedFields['available_spots'] = $this->available_spots;
        }

        $this->mappedFields = $mappedFields;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
