<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventReferenceUpdateRequest')]
readonly class EventReferenceUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'event_id', type: 'integer', nullable: true)]
    public ?int $event_id;
    #[OA\Property(description: 'source_system', type: 'string', nullable: true)]
    public ?string $source_system;
    #[OA\Property(description: 'source_type', type: 'string', nullable: true)]
    public ?string $source_type;
    #[OA\Property(description: 'source_id', type: 'string', nullable: true)]
    public ?string $source_id;
    #[OA\Property(description: 'relation', type: 'string', nullable: true)]
    public ?string $relation;
    #[OA\Property(description: 'metadata', type: 'object', nullable: true)]
    public ?array $metadata;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'permit_empty|is_natural_no_zero|is_not_unique[events.id]',
            'source_system' => 'permit_empty|string|max_length[255]',
            'source_type' => 'permit_empty|string|max_length[255]',
            'source_id' => 'permit_empty|string|max_length[255]',
            'relation' => 'permit_empty|string|max_length[255]',
            'metadata' => 'permit_empty',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->event_id = isset($data['event_id']) ? (int) $data['event_id'] : null;
        $this->source_system = $data['source_system'] ?? null;
        $this->source_type = $data['source_type'] ?? null;
        $this->source_id = $data['source_id'] ?? null;
        $this->relation = $data['relation'] ?? null;
        $this->metadata = isset($data['metadata']) ? (array) $data['metadata'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'event_id' => $this->event_id,
            'source_system' => $this->source_system,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'relation' => $this->relation,
            'metadata' => $this->metadata,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
