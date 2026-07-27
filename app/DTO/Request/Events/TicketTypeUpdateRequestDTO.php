<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TicketTypeUpdateRequest')]
readonly class TicketTypeUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'event_id', type: 'integer', nullable: true)]
    public ?int $event_id;
    #[OA\Property(description: 'Concrete scheduled occurrence', type: 'integer', nullable: true)]
    public ?int $occurrence_id;
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    #[OA\Property(description: 'price', type: 'number', format: 'float', nullable: true)]
    public ?float $price;
    #[OA\Property(description: 'capacity', type: 'integer', nullable: true)]
    public ?int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer', nullable: true)]
    public ?int $available_spots;
    #[OA\Property(description: 'sales_start', type: 'string', format: 'date-time', nullable: true)]
    public ?string $sales_start;
    #[OA\Property(description: 'sales_end', type: 'string', format: 'date-time', nullable: true)]
    public ?string $sales_end;

    public function rules(): array
    {
        return [
            'event_id' => 'permit_empty|is_natural_no_zero|is_not_unique[events.id]',
            'occurrence_id' => 'permit_empty|is_natural_no_zero|is_not_unique[occurrences.id]',
            'name' => 'permit_empty|string|max_length[255]',
            'price' => 'permit_empty|decimal',
            'capacity' => 'permit_empty|integer',
            'available_spots' => 'permit_empty|integer',
            'sales_start' => 'permit_empty|valid_date',
            'sales_end' => 'permit_empty|valid_date',
        ];
    }

    protected function map(array $data): void
    {
        $this->event_id = isset($data['event_id']) ? (int) $data['event_id'] : null;
        $this->occurrence_id = isset($data['occurrence_id']) ? (int) $data['occurrence_id'] : null;
        $this->name = $data['name'] ?? null;
        $this->price = isset($data['price']) ? (float) $data['price'] : null;
        $this->capacity = isset($data['capacity']) ? (int) $data['capacity'] : null;
        $this->available_spots = isset($data['available_spots']) ? (int) $data['available_spots'] : null;
        $this->sales_start = $data['sales_start'] ?? null;
        $this->sales_end = $data['sales_end'] ?? null;
    }

    public function toArray(): array
    {
        return array_filter([
            'event_id' => $this->event_id,
            'occurrence_id' => $this->occurrence_id,
            'name' => $this->name,
            'price' => $this->price,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'sales_start' => $this->sales_start,
            'sales_end' => $this->sales_end,
        ], fn ($v) => $v !== null);
    }
}
