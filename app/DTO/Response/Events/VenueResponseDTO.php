<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use App\Traits\DTO\NormalizesResponseTimestamps;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'VenueResponse',
    title: 'Venue Response',
    required: ["id","name","slug","is_active","translations","localized"]
)]
final readonly class VenueResponseDTO implements DataTransferObjectInterface
{
    use NormalizesResponseTimestamps;

    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'name', type: 'string')]
        public string $name,
        #[OA\Property(description: 'slug', type: 'string')]
        public string $slug,
        #[OA\Property(description: 'description', type: 'string', nullable: true)]
        public ?string $description,
        /** @var list<array{locale: string, fields: array<string, string>}> */
        #[OA\Property(description: 'All stored localized content rows', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations,
        /** @var array{locale: string, name?: string, description?: string} */
        #[OA\Property(description: 'Content resolved from Accept-Language with field-level fallback', type: 'object')]
        public array $localized,
        #[OA\Property(description: 'capacity', type: 'integer', nullable: true)]
        public ?int $capacity,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            slug: (string) ($data['slug'] ?? ''),
            description: $data['description'] ?? null,
            translations: is_array($data['translations'] ?? null) ? $data['translations'] : [],
            localized: is_array($data['localized'] ?? null) ? $data['localized'] : [],
            capacity: isset($data['capacity']) ? (int) $data['capacity'] : null,
            is_active: (bool) ($data['is_active'] ?? false),
            createdAt: self::normalizeResponseTimestamp($data['created_at'] ?? null),
            updatedAt: self::normalizeResponseTimestamp($data['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'translations' => $this->translations,
            'localized' => $this->localized,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
