<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BookingCreateRequest')]
readonly class BookingCreateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->uuid = (string) ($data['uuid'] ?? '');
        $this->user_id = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $this->guest_email = $data['guest_email'] ?? null;
        $this->total_amount = (float) ($data['total_amount'] ?? 0);
        $this->status = (string) ($data['status'] ?? '');
        $this->reserved_until = $data['reserved_until'] ?? null;
        $this->ticket_type_id = (int) ($data['ticket_type_id'] ?? 0);
        $this->quantity = (int) ($data['quantity'] ?? 0);
        $this->holder_name = $data['holder_name'] ?? null;
        $this->holder_email = $data['holder_email'] ?? null;

    }

    #[OA\Property(description: 'uuid', type: 'string')]
    public string $uuid;
    #[OA\Property(description: 'user_id', type: 'integer', nullable: true)]
    public ?int $user_id;
    #[OA\Property(description: 'guest_email', type: 'string', format: 'email', nullable: true)]
    public ?string $guest_email;
    #[OA\Property(description: 'total_amount', type: 'number', format: 'float')]
    public float $total_amount;
    #[OA\Property(description: 'status', type: 'string')]
    public string $status;
    #[OA\Property(description: 'reserved_until', type: 'string', format: 'date-time', nullable: true)]
    public ?string $reserved_until;
    #[OA\Property(description: 'ticket_type_id', type: 'integer')]
    public int $ticket_type_id;
    #[OA\Property(description: 'quantity', type: 'integer')]
    public int $quantity;
    #[OA\Property(description: 'holder_name', type: 'string', nullable: true)]
    public ?string $holder_name;
    #[OA\Property(description: 'holder_email', type: 'string', format: 'email', nullable: true)]
    public ?string $holder_email;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]|is_unique[bookings.uuid]',
            'user_id' => 'permit_empty|integer',
            'guest_email' => 'permit_empty|string|valid_email|max_length[255]',
            'total_amount' => 'required|decimal',
            'status' => 'required|string|max_length[255]',
            'reserved_until' => 'permit_empty|valid_date',
            'ticket_type_id' => 'required|is_natural_no_zero|is_not_unique[ticket_types.id]',
            'quantity' => 'required|integer|greater_than[0]',
            'holder_name' => 'permit_empty|string|max_length[255]',
            'holder_email' => 'permit_empty|string|valid_email|max_length[255]',
        ];
    }

    protected function map(array $data): void
    {
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'guest_email' => $this->guest_email,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'reserved_until' => $this->reserved_until,
            'ticket_type_id' => $this->ticket_type_id,
            'quantity' => $this->quantity,
            'holder_name' => $this->holder_name,
            'holder_email' => $this->holder_email,
        ];
    }
}
