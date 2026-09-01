<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventTypeUpdateRequest')]
readonly class EventTypeUpdateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->slug = $data['slug'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->translations = array_key_exists('translations', $data) && is_array($data['translations'])
            ? array_values($data['translations'])
            : null;
        $this->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;

    }

    #[OA\Property(description: 'slug', type: 'string', nullable: true)]
    public ?string $slug;
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    /** @var list<array<string, mixed>>|null */
    #[OA\Property(description: 'Localized names keyed by locale code', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
    public ?array $translations;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'slug' => 'permit_empty|string|max_length[255]',
            'name' => 'permit_empty|string|max_length[255]',
            'translations' => 'permit_empty',
            'sort_order' => 'permit_empty|integer',
            'translations.*.slug' => 'required_with[translations]|string|max_length[191]',
            'is_active' => 'permit_empty|boolean_like',
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
        return array_filter([
            'slug' => $this->slug,
            'name' => $this->name,
            'translations' => $this->translations,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
