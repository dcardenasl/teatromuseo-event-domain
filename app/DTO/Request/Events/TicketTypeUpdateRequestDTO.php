<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TicketTypeUpdateRequest')]
readonly class TicketTypeUpdateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->event_id = array_key_exists('event_id', $data) && $data['event_id'] !== null && $data['event_id'] !== '' ? (int) $data['event_id'] : null;
        $this->occurrence_id = array_key_exists('occurrence_id', $data) && $data['occurrence_id'] !== null && $data['occurrence_id'] !== '' ? (int) $data['occurrence_id'] : null;
        $this->name = array_key_exists('name', $data) && $data['name'] !== null ? (string) $data['name'] : null;
        $this->translations = array_key_exists('translations', $data) && is_array($data['translations']) ? array_values($data['translations']) : null;
        $this->price = array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '' ? (float) $data['price'] : null;
        $this->capacity = array_key_exists('capacity', $data) && $data['capacity'] !== null && $data['capacity'] !== '' ? (int) $data['capacity'] : null;
        $this->available_spots = array_key_exists('available_spots', $data) && $data['available_spots'] !== null && $data['available_spots'] !== '' ? (int) $data['available_spots'] : null;
        $this->sales_start = array_key_exists('sales_start', $data) && $data['sales_start'] !== null ? (string) $data['sales_start'] : null;
        $this->sales_end = array_key_exists('sales_end', $data) && $data['sales_end'] !== null ? (string) $data['sales_end'] : null;

        $mappedFields = [];
        if ($this->event_id !== null) {
            $mappedFields['event_id'] = $this->event_id;
        }
        if (array_key_exists('occurrence_id', $data)) {
            $mappedFields['occurrence_id'] = $this->occurrence_id;
        }
        if ($this->name !== null) {
            $mappedFields['name'] = $this->name;
        }
        if ($this->translations !== null) {
            $mappedFields['translations'] = $this->translations;
        }
        if ($this->price !== null) {
            $mappedFields['price'] = $this->price;
        }
        if ($this->capacity !== null) {
            $mappedFields['capacity'] = $this->capacity;
        }
        if ($this->available_spots !== null) {
            $mappedFields['available_spots'] = $this->available_spots;
        }
        if ($this->sales_start !== null) {
            $mappedFields['sales_start'] = $this->sales_start;
        }
        if ($this->sales_end !== null) {
            $mappedFields['sales_end'] = $this->sales_end;
        }

        $this->mappedFields = $mappedFields;

    }

    #[OA\Property(description: 'event_id', type: 'integer', nullable: true)]
    public ?int $event_id;
    #[OA\Property(description: 'Concrete scheduled occurrence', type: 'integer', nullable: true)]
    public ?int $occurrence_id;
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    /** @var list<array<string, mixed>>|null */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', nullable: true, items: new OA\Items(type: 'object'))]
    public ?array $translations;
    #[OA\Property(description: 'price', type: 'number', format: 'float', nullable: true)]
    public ?float $price;
    #[OA\Property(description: 'capacity', type: 'integer', nullable: true)]
    public ?int $capacity;
    #[OA\Property(description: 'available_spots', type: 'integer', nullable: true)]
    public ?int $available_spots;
    #[OA\Property(description: 'sales_start', type: 'string', format: 'date-time', nullable: true)]
    public ?string $sales_start;
    #[OA\Property(description: 'sales_end', type: 'string', format: 'date-time', nullable: true)]
    public ?string $sales_end;

    public function rules(): array
    {
        return [
            'event_id' => 'permit_empty|is_natural_no_zero|is_not_unique[events.id]',
            'occurrence_id' => 'permit_empty|is_natural_no_zero|is_not_unique[occurrences.id]',
            'name' => 'permit_empty|string|max_length[255]',
            'translations' => 'permit_empty',
            'price' => 'permit_empty|decimal',
            'capacity' => 'permit_empty|integer',
            'available_spots' => 'permit_empty|integer',
            'sales_start' => 'permit_empty|valid_date',
            'sales_end' => 'permit_empty|valid_date',
        ];
    }

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * occurrence_id is the only nullable column on ticket_types — it
     * preserves an explicit null so it reaches toArray() and actually
     * clears the column (e.g. detaching a ticket type from a specific
     * occurrence back to "any occurrence"). Every other field is NOT NULL,
     * so an explicit null there is treated the same as omitting the field —
     * the bug this fixes is array_filter() silently dropping every null,
     * which made it impossible to ever clear the one field that can be.
     */
    protected function map(array $data): void
    {
    }

    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
