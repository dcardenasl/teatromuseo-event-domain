<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'VenueUpdateRequest')]
readonly class VenueUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    #[OA\Property(description: 'slug', type: 'string', nullable: true)]
    public ?string $slug;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;
    #[OA\Property(description: 'capacity', type: 'integer', nullable: true)]
    public ?int $capacity;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'permit_empty|string|max_length[255]',
            'slug' => 'permit_empty|string|max_length[255]',
            'description' => 'permit_empty|string',
            'capacity' => 'permit_empty|integer',
            'is_active' => 'permit_empty|boolean_like',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->name = $data['name'] ?? null;
        $this->slug = $data['slug'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->capacity = isset($data['capacity']) ? (int) $data['capacity'] : null;
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
