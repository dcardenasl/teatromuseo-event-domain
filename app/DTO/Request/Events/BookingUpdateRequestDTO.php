<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BookingUpdateRequest')]
readonly class BookingUpdateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->uuid = array_key_exists('uuid', $data) && $data['uuid'] !== null ? (string) $data['uuid'] : null;
        $this->user_id = array_key_exists('user_id', $data) && $data['user_id'] !== null && $data['user_id'] !== '' ? (int) $data['user_id'] : null;
        $this->guest_email = array_key_exists('guest_email', $data) && $data['guest_email'] !== null && $data['guest_email'] !== '' ? (string) $data['guest_email'] : null;
        $this->total_amount = array_key_exists('total_amount', $data) && $data['total_amount'] !== null && $data['total_amount'] !== '' ? (float) $data['total_amount'] : null;
        $this->status = array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : null;
        $this->reserved_until = array_key_exists('reserved_until', $data) && $data['reserved_until'] !== null && $data['reserved_until'] !== '' ? (string) $data['reserved_until'] : null;

        $mappedFields = [];
        if ($this->uuid !== null) {
            $mappedFields['uuid'] = $this->uuid;
        }
        if (array_key_exists('user_id', $data)) {
            $mappedFields['user_id'] = $this->user_id;
        }
        if (array_key_exists('guest_email', $data)) {
            $mappedFields['guest_email'] = $this->guest_email;
        }
        if ($this->total_amount !== null) {
            $mappedFields['total_amount'] = $this->total_amount;
        }
        if ($this->status !== null) {
            $mappedFields['status'] = $this->status;
        }
        if (array_key_exists('reserved_until', $data)) {
            $mappedFields['reserved_until'] = $this->reserved_until;
        }

        $this->mappedFields = $mappedFields;

    }

    #[OA\Property(description: 'uuid', type: 'string', nullable: true)]
    public ?string $uuid;
    #[OA\Property(description: 'user_id', type: 'integer', nullable: true)]
    public ?int $user_id;
    #[OA\Property(description: 'guest_email', type: 'string', format: 'email', nullable: true)]
    public ?string $guest_email;
    #[OA\Property(description: 'total_amount', type: 'number', format: 'float', nullable: true)]
    public ?float $total_amount;
    #[OA\Property(description: 'status', type: 'string', nullable: true)]
    public ?string $status;
    #[OA\Property(description: 'reserved_until', type: 'string', format: 'date-time', nullable: true)]
    public ?string $reserved_until;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]',
            'user_id' => 'permit_empty|integer',
            'guest_email' => 'permit_empty|string|valid_email|max_length[255]',
            'total_amount' => 'permit_empty|decimal',
            'status' => 'permit_empty|string|max_length[255]',
            'reserved_until' => 'permit_empty|valid_date',
        ];
    }

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * user_id, guest_email, reserved_until are nullable columns on
     * bookings — each preserves an explicit null so it reaches toArray()
     * and actually clears the column (e.g. converting a guest booking to
     * anonymous, or cancelling a reservation hold). uuid/total_amount/
     * status are NOT NULL, so an explicit null there is treated the same
     * as omitting the field — the bug this fixes is array_filter()
     * silently dropping every null, which made it impossible to ever
     * clear the fields that can be.
     */
    protected function map(array $data): void
    {
    }

    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
