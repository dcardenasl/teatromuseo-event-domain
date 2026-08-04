<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventTypeCreateRequest')]
readonly class EventTypeCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'slug', type: 'string')]
    public string $slug;
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    /** @var list<array<string, mixed>> */
    #[OA\Property(description: 'Localized names keyed by locale code', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;
    #[OA\Property(description: 'Managed exclusively by the reorder endpoint', type: 'integer', default: 0)]
    public int $sort_order;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'slug' => 'required|string|max_length[255]|is_unique[event_types.slug]',
            'name' => 'required|string|max_length[255]',
            'translations' => 'permit_empty',
            'sort_order' => 'permit_empty|integer',
            'translations.*.slug' => 'required_with[translations]|string|max_length[191]',
            'is_active' => 'required|boolean_like',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->slug = (string) ($data['slug'] ?? '');
        $this->name = (string) ($data['name'] ?? '');
        $this->translations = is_array($data['translations'] ?? null) ? array_values($data['translations']) : [];
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
        $this->is_active = (bool) ($data['is_active'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'translations' => $this->translations,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
