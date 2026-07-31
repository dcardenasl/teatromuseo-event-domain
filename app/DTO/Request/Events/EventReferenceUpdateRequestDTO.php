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

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * metadata is the only nullable column on event_references — it
     * preserves an explicit null so it reaches toArray() and actually
     * clears the column. Every other field is NOT NULL, so an explicit
     * null there is treated the same as omitting the field — the bug this
     * fixes is array_filter() silently dropping every null, which made it
     * impossible to ever clear the one field that can be.
     *
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->event_id = array_key_exists('event_id', $data) && $data['event_id'] !== null && $data['event_id'] !== '' ? (int) $data['event_id'] : null;
        $this->source_system = array_key_exists('source_system', $data) && $data['source_system'] !== null ? (string) $data['source_system'] : null;
        $this->source_type = array_key_exists('source_type', $data) && $data['source_type'] !== null ? (string) $data['source_type'] : null;
        $this->source_id = array_key_exists('source_id', $data) && $data['source_id'] !== null ? (string) $data['source_id'] : null;
        $this->relation = array_key_exists('relation', $data) && $data['relation'] !== null ? (string) $data['relation'] : null;
        $this->metadata = array_key_exists('metadata', $data) && $data['metadata'] !== null ? (array) $data['metadata'] : null;

        $mappedFields = [];
        if ($this->event_id !== null) {
            $mappedFields['event_id'] = $this->event_id;
        }
        if ($this->source_system !== null) {
            $mappedFields['source_system'] = $this->source_system;
        }
        if ($this->source_type !== null) {
            $mappedFields['source_type'] = $this->source_type;
        }
        if ($this->source_id !== null) {
            $mappedFields['source_id'] = $this->source_id;
        }
        if ($this->relation !== null) {
            $mappedFields['relation'] = $this->relation;
        }
        if (array_key_exists('metadata', $data)) {
            $mappedFields['metadata'] = $this->metadata;
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
