<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use App\Traits\DTO\NormalizesLocalizedPayload;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EventTypeResponse',
    title: 'EventType Response',
    required: ["id","name","sort_order","is_active","translations","localized","slugs"]
)]
final readonly class EventTypeResponseDTO implements DataTransferObjectInterface
{
    use NormalizesLocalizedPayload;

    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'slug', type: 'string')]
        public string $slug,
        #[OA\Property(description: 'name', type: 'string')]
        public string $name,
        #[OA\Property(description: 'sort_order', type: 'integer')]
        public int $sort_order,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        /** @var array<string, string> locale => localized public slug */
        public array $slugs = [],
        /** @var list<array<string, string>> */
        #[OA\Property(description: 'All stored localized content rows', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations = [],
        /** @var array<string, string> */
        #[OA\Property(description: 'Name resolved from Accept-Language', type: 'object')]
        public array $localized = [],
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
        $slugs = is_array($data['slugs'] ?? null) ? $data['slugs'] : [];
        $translations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        foreach ($translations as &$translation) {
            if (! is_array($translation)) {
                continue;
            }
            $locale = strtolower((string) ($translation['locale'] ?? ''));
            if ($locale !== '' && isset($slugs[$locale])) {
                $translation['slug'] = (string) $slugs[$locale];
            }
        }
        unset($translation);

        return new static(
            id: (int) ($data['id'] ?? 0),
            slug: (string) ($data['slug'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            translations: self::normalizeTranslationRows($translations),
            localized: self::normalizeLocalized($data['localized'] ?? null),
            sort_order: (int) ($data['sort_order'] ?? 0),
            is_active: (bool) ($data['is_active'] ?? false),
            slugs: is_array($data['slugs'] ?? null) ? $data['slugs'] : [],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'translations' => $this->translations,
            'localized' => $this->localized,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'slugs' => $this->slugs,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
