<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use App\Traits\DTO\NormalizesResponseTimestamps;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TicketTypeResponse',
    title: 'TicketType Response',
    required: ["id","event_id","name","price","capacity","available_spots","sales_start","sales_end","translations","localized"]
)]
final readonly class TicketTypeResponseDTO implements DataTransferObjectInterface
{
    use NormalizesResponseTimestamps;

    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'event_id', type: 'integer')]
        public int $event_id,
        #[OA\Property(description: 'Concrete scheduled occurrence', type: 'integer', nullable: true)]
        public ?int $occurrence_id,
        #[OA\Property(description: 'name', type: 'string')]
        public string $name,
        /** @var list<array{locale: string, fields: array<string, string>}> */
        #[OA\Property(description: 'All stored localized content rows', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations,
        /** @var array{locale: string, name?: string} */
        #[OA\Property(description: 'Content resolved from Accept-Language with field-level fallback', type: 'object')]
        public array $localized,
        #[OA\Property(description: 'price', type: 'number', format: 'float')]
        public float $price,
        #[OA\Property(description: 'capacity', type: 'integer')]
        public int $capacity,
        #[OA\Property(description: 'available_spots', type: 'integer')]
        public int $available_spots,
        #[OA\Property(description: 'sales_start', type: 'string', format: 'date-time')]
        public string $sales_start,
        #[OA\Property(description: 'sales_end', type: 'string', format: 'date-time')]
        public string $sales_end,
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
            event_id: (int) ($data['event_id'] ?? 0),
            occurrence_id: isset($data['occurrence_id']) ? (int) $data['occurrence_id'] : null,
            name: (string) ($data['name'] ?? ''),
            translations: is_array($data['translations'] ?? null) ? $data['translations'] : [],
            localized: is_array($data['localized'] ?? null) ? $data['localized'] : [],
            price: (float) ($data['price'] ?? 0),
            capacity: (int) ($data['capacity'] ?? 0),
            available_spots: (int) ($data['available_spots'] ?? 0),
            sales_start: (string) ($data['sales_start'] ?? ''),
            sales_end: (string) ($data['sales_end'] ?? ''),
            createdAt: self::normalizeResponseTimestamp($data['created_at'] ?? null),
            updatedAt: self::normalizeResponseTimestamp($data['updated_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'occurrence_id' => $this->occurrence_id,
            'name' => $this->name,
            'translations' => $this->translations,
            'localized' => $this->localized,
            'price' => $this->price,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'sales_start' => $this->sales_start,
            'sales_end' => $this->sales_end,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
