<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TicketTypeCreateRequest')]
readonly class TicketTypeCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'event_id', type: 'integer')]
    public int $event_id;
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    #[OA\Property(description: 'price', type: 'number', format: 'float')]
    public float $price;
    #[OA\Property(description: 'capacity', type: 'integer')]
    public int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer')]
    public int $available_spots;
    #[OA\Property(description: 'sales_start', type: 'string', format: 'date-time')]
    public string $sales_start;
    #[OA\Property(description: 'sales_end', type: 'string', format: 'date-time')]
    public string $sales_end;

    public function rules(): array
    {
        return [
            'event_id' => 'required|is_natural_no_zero|is_not_unique[events.id]',
            'name' => 'required|string|max_length[255]',
            'price' => 'required|decimal',
            'capacity' => 'required|integer',
            'available_spots' => 'required|integer',
            'sales_start' => 'required|valid_date',
            'sales_end' => 'required|valid_date',
        ];
    }

    protected function map(array $data): void
    {
        $this->event_id = (int) ($data['event_id'] ?? 0);
        $this->name = (string) ($data['name'] ?? '');
        $this->price = (float) ($data['price'] ?? 0);
        $this->capacity = (int) ($data['capacity'] ?? 0);
        $this->available_spots = (int) ($data['available_spots'] ?? 0);
        $this->sales_start = (string) ($data['sales_start'] ?? '');
        $this->sales_end = (string) ($data['sales_end'] ?? '');
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->event_id,
            'name' => $this->name,
            'price' => $this->price,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'sales_start' => $this->sales_start,
            'sales_end' => $this->sales_end,
        ];
    }
}
