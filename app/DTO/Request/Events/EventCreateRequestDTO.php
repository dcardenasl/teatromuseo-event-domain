<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventCreateRequest')]
readonly class EventCreateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->uuid = (string) ($data['uuid'] ?? '');
        $this->title = (string) ($data['title'] ?? '');
        $this->event_type = (string) ($data['event_type'] ?? 'function');
        $this->description = (string) ($data['description'] ?? '');
        $this->cover_file_id = isset($data['cover_file_id']) ? (int) $data['cover_file_id'] : null;
        $this->gallery_file_ids = $data['gallery_file_ids'] ?? null;
        $this->translations = is_array($data['translations'] ?? null) ? array_values($data['translations']) : [];
        $this->status = (string) ($data['status'] ?? '');

    }

    #[OA\Property(description: 'uuid', type: 'string')]
    public string $uuid;
    #[OA\Property(description: 'title', type: 'string')]
    public string $title;
    #[OA\Property(description: 'Programming type slug', type: 'string', example: 'function')]
    public string $event_type;
    #[OA\Property(description: 'description', type: 'string')]
    public string $description;
    #[OA\Property(description: 'cover_file_id', type: 'integer', nullable: true)]
    public ?int $cover_file_id;
    #[OA\Property(description: 'gallery_file_ids', type: 'string', nullable: true)]
    public ?string $gallery_file_ids;
    /** @var list<array<string, mixed>> */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;
    #[OA\Property(description: 'status', type: 'string')]
    public string $status;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]|is_unique[events.uuid]',
            'title' => 'required|string|max_length[255]',
            'event_type' => 'required|string|max_length[80]|is_not_unique[event_types.slug]',
            'description' => 'required|string',
            'cover_file_id' => 'permit_empty|integer',
            'gallery_file_ids' => 'permit_empty|string',
            'translations' => 'permit_empty',
            'status' => 'required|string|max_length[255]',
        ];
    }

    protected function map(array $data): void
    {
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'event_type' => $this->event_type,
            'description' => $this->description,
            'cover_file_id' => $this->cover_file_id,
            'gallery_file_ids' => $this->gallery_file_ids,
            'translations' => $this->translations,
            'status' => $this->status,
        ];
    }
}
