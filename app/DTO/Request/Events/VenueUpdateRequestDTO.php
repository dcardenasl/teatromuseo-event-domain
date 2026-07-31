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
    /** @var list<array<string, mixed>>|null */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', nullable: true, items: new OA\Items(type: 'object'))]
    public ?array $translations;
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
            'translations' => 'permit_empty',
            'capacity' => 'permit_empty|integer',
            'is_active' => 'permit_empty|boolean_like',
        ];
    }

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * NOT NULL columns (name, slug, is_active) never accept an explicit null
     * — treated the same as omitting the field, matching the DB constraint.
     * Nullable columns (description, capacity) preserve an explicit null so
     * it reaches toArray() and actually clears the column — the bug this
     * fixes is array_filter() silently dropping every null, which made it
     * impossible to ever clear a nullable field via update.
     *
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->name = array_key_exists('name', $data) && $data['name'] !== null ? (string) $data['name'] : null;
        $this->slug = array_key_exists('slug', $data) && $data['slug'] !== null ? (string) $data['slug'] : null;
        $this->description = array_key_exists('description', $data) && $data['description'] !== null && $data['description'] !== '' ? (string) $data['description'] : null;
        $this->translations = array_key_exists('translations', $data) && is_array($data['translations']) ? array_values($data['translations']) : null;
        $this->capacity = array_key_exists('capacity', $data) && $data['capacity'] !== null && $data['capacity'] !== '' ? (int) $data['capacity'] : null;
        $this->is_active = array_key_exists('is_active', $data) && $data['is_active'] !== null ? (bool) $data['is_active'] : null;

        $mappedFields = [];
        if ($this->name !== null) {
            $mappedFields['name'] = $this->name;
        }
        if ($this->slug !== null) {
            $mappedFields['slug'] = $this->slug;
        }
        if (array_key_exists('description', $data)) {
            $mappedFields['description'] = $this->description;
        }
        if ($this->translations !== null) {
            $mappedFields['translations'] = $this->translations;
        }
        if (array_key_exists('capacity', $data)) {
            $mappedFields['capacity'] = $this->capacity;
        }
        if ($this->is_active !== null) {
            $mappedFields['is_active'] = $this->is_active;
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
