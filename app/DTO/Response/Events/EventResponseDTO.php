<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use App\Traits\DTO\NormalizesResponseTimestamps;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EventResponse',
    title: 'Event Response',
    required: ["id","uuid","title","event_type","description","status","translations","localized"]
)]
final readonly class EventResponseDTO implements DataTransferObjectInterface
{
    use NormalizesResponseTimestamps;

    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'uuid', type: 'string')]
        public string $uuid,
        #[OA\Property(description: 'title', type: 'string')]
        public string $title,
        #[OA\Property(description: 'Programming type', type: 'string')]
        public string $event_type,
        #[OA\Property(description: 'description', type: 'string')]
        public string $description,
        #[OA\Property(description: 'cover_file_id', type: 'integer', nullable: true)]
        public ?int $cover_file_id,
        #[OA\Property(description: 'gallery_file_ids', type: 'string', nullable: true)]
        public ?string $gallery_file_ids,
        /** @var list<array{locale: string, fields: array<string, string>}> */
        #[OA\Property(description: 'All stored localized content rows', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations,
        /** @var array{locale: string, title?: string, description?: string} */
        #[OA\Property(description: 'Content resolved from Accept-Language with field-level fallback', type: 'object')]
        public array $localized,
        #[OA\Property(description: 'Public routing slug resolved for the request locale', type: 'string')]
        public string $slug,
        /** @var array<string, string> */
        #[OA\Property(description: 'Every public routing slug, keyed by locale', type: 'object')]
        public array $slugs,
        #[OA\Property(description: 'start_time', type: 'string', format: 'date-time')]
        public ?string $start_time,
        #[OA\Property(description: 'end_time', type: 'string', format: 'date-time')]
        public ?string $end_time,
        #[OA\Property(description: 'venue', type: 'string')]
        public ?string $venue,
        #[OA\Property(description: 'capacity', type: 'integer')]
        public ?int $capacity,
        #[OA\Property(description: 'available_spots', type: 'integer')]
        public ?int $available_spots,
        #[OA\Property(description: 'status', type: 'string')]
        public string $status,
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
            uuid: (string) ($data['uuid'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            event_type: (string) ($data['event_type'] ?? 'function'),
            description: (string) ($data['description'] ?? ''),
            cover_file_id: isset($data['cover_file_id']) ? (int) $data['cover_file_id'] : null,
            gallery_file_ids: $data['gallery_file_ids'] ?? null,
            translations: is_array($data['translations'] ?? null) ? $data['translations'] : [],
            localized: is_array($data['localized'] ?? null) ? $data['localized'] : [],
            slug: (string) ($data['slug'] ?? ''),
            slugs: is_array($data['slugs'] ?? null) ? $data['slugs'] : [],
            start_time: isset($data['start_time']) ? (string) $data['start_time'] : null,
            end_time: isset($data['end_time']) ? (string) $data['end_time'] : null,
            venue: isset($data['venue']) ? (string) $data['venue'] : null,
            capacity: isset($data['capacity']) ? (int) $data['capacity'] : null,
            available_spots: isset($data['available_spots']) ? (int) $data['available_spots'] : null,
            status: (string) ($data['status'] ?? ''),
            createdAt: self::normalizeResponseTimestamp($data['created_at'] ?? null),
            updatedAt: self::normalizeResponseTimestamp($data['updated_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'event_type' => $this->event_type,
            'description' => $this->description,
            'cover_file_id' => $this->cover_file_id,
            'gallery_file_ids' => $this->gallery_file_ids,
            'translations' => $this->translations,
            'localized' => $this->localized,
            'slug' => $this->slug,
            'slugs' => $this->slugs,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'venue' => $this->venue,
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
