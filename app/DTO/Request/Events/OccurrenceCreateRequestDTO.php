<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'OccurrenceCreateRequest')]
readonly class OccurrenceCreateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->event_id = (int) ($data['event_id'] ?? 0);
        $this->venue_id = isset($data['venue_id']) ? (int) $data['venue_id'] : null;
        $this->start_time = (string) ($data['start_time'] ?? '');
        $this->end_time = (string) ($data['end_time'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->capacity = (int) ($data['capacity'] ?? 0);
        $this->available_spots = (int) ($data['available_spots'] ?? 0);

    }

    #[OA\Property(description: 'event_id', type: 'integer')]
    public int $event_id;
    #[OA\Property(description: 'venue_id', type: 'integer', nullable: true)]
    public ?int $venue_id;
    #[OA\Property(description: 'start_time', type: 'string', format: 'date-time')]
    public string $start_time;
    #[OA\Property(description: 'end_time', type: 'string', format: 'date-time')]
    public string $end_time;
    #[OA\Property(description: 'status', type: 'string')]
    public string $status;
    #[OA\Property(description: 'capacity', type: 'integer')]
    public int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer')]
    public int $available_spots;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|is_natural_no_zero|is_not_unique[events.id]',
            'venue_id' => 'permit_empty|is_natural_no_zero|is_not_unique[venues.id]',
            'start_time' => 'required|valid_date',
            'end_time' => 'required|valid_date',
            'status' => 'required|string|max_length[255]',
            'capacity' => 'required|integer',
            'available_spots' => 'required|integer',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->event_id,
            'venue_id' => $this->venue_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
        ];
    }
}
