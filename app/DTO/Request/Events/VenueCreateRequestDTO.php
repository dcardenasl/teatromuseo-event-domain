<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'VenueCreateRequest')]
readonly class VenueCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    #[OA\Property(description: 'slug', type: 'string')]
    public string $slug;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;
    /** @var list<array<string, mixed>> */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;
    #[OA\Property(description: 'capacity', type: 'integer', nullable: true)]
    public ?int $capacity;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max_length[255]',
            'slug' => 'required|string|max_length[255]|is_unique[venues.slug]',
            'description' => 'permit_empty|string',
            'translations' => 'permit_empty',
            'capacity' => 'permit_empty|integer',
            'is_active' => 'required|boolean_like',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->name = (string) ($data['name'] ?? '');
        $this->slug = (string) ($data['slug'] ?? '');
        $this->description = $data['description'] ?? null;
        $this->translations = is_array($data['translations'] ?? null) ? array_values($data['translations']) : [];
        $this->capacity = isset($data['capacity']) ? (int) $data['capacity'] : null;
        $this->is_active = (bool) ($data['is_active'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'translations' => $this->translations,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
        ];
    }
}
