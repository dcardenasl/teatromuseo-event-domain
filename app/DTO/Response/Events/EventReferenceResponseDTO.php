<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use App\Traits\DTO\NormalizesResponseTimestamps;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EventReferenceResponse',
    title: 'EventReference Response',
    required: ["id","event_id","source_system","source_type","source_id","relation"]
)]
final readonly class EventReferenceResponseDTO implements DataTransferObjectInterface
{
    use NormalizesResponseTimestamps;

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'event_id', type: 'integer')]
        public int $event_id,
        #[OA\Property(description: 'source_system', type: 'string')]
        public string $source_system,
        #[OA\Property(description: 'source_type', type: 'string')]
        public string $source_type,
        #[OA\Property(description: 'source_id', type: 'string')]
        public string $source_id,
        #[OA\Property(description: 'relation', type: 'string')]
        public string $relation,
        #[OA\Property(description: 'metadata', type: 'object', nullable: true)]
        public ?array $metadata,
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
            event_id: (int) ($data['event_id'] ?? 0),
            source_system: (string) ($data['source_system'] ?? ''),
            source_type: (string) ($data['source_type'] ?? ''),
            source_id: (string) ($data['source_id'] ?? ''),
            relation: (string) ($data['relation'] ?? ''),
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
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
            'event_id' => $this->event_id,
            'source_system' => $this->source_system,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'relation' => $this->relation,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
