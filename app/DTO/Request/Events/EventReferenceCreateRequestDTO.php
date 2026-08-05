<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventReferenceCreateRequest')]
readonly class EventReferenceCreateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->event_id = (int) ($data['event_id'] ?? 0);
        $this->source_system = (string) ($data['source_system'] ?? '');
        $this->source_type = (string) ($data['source_type'] ?? '');
        $this->source_id = (string) ($data['source_id'] ?? '');
        $this->relation = (string) ($data['relation'] ?? '');
        $this->metadata = isset($data['metadata']) ? (array) $data['metadata'] : null;

    }

    #[OA\Property(description: 'event_id', type: 'integer')]
    public int $event_id;
    #[OA\Property(description: 'source_system', type: 'string')]
    public string $source_system;
    #[OA\Property(description: 'source_type', type: 'string')]
    public string $source_type;
    #[OA\Property(description: 'source_id', type: 'string')]
    public string $source_id;
    #[OA\Property(description: 'relation', type: 'string')]
    public string $relation;
    /** @var array<string, mixed>|null */
    #[OA\Property(description: 'metadata', type: 'object', nullable: true)]
    public ?array $metadata;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|is_natural_no_zero|is_not_unique[events.id]',
            'source_system' => 'required|string|max_length[255]',
            'source_type' => 'required|string|max_length[255]',
            'source_id' => 'required|string|max_length[255]',
            'relation' => 'required|string|max_length[255]',
            'metadata' => 'permit_empty',
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
        return [
            'event_id' => $this->event_id,
            'source_system' => $this->source_system,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'relation' => $this->relation,
            'metadata' => $this->metadata,
        ];
    }
}
