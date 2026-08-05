<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TicketCreateRequest')]
readonly class TicketCreateRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $this->uuid = (string) ($data['uuid'] ?? '');
        $this->booking_id = (int) ($data['booking_id'] ?? 0);
        $this->ticket_type_id = (int) ($data['ticket_type_id'] ?? 0);
        $this->holder_name = (string) ($data['holder_name'] ?? '');
        $this->holder_email = (string) ($data['holder_email'] ?? '');
        $this->qr_code_token = (string) ($data['qr_code_token'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->checked_in_at = $data['checked_in_at'] ?? null;

    }

    #[OA\Property(description: 'uuid', type: 'string')]
    public string $uuid;
    #[OA\Property(description: 'booking_id', type: 'integer')]
    public int $booking_id;
    #[OA\Property(description: 'ticket_type_id', type: 'integer')]
    public int $ticket_type_id;
    #[OA\Property(description: 'holder_name', type: 'string')]
    public string $holder_name;
    #[OA\Property(description: 'holder_email', type: 'string', format: 'email')]
    public string $holder_email;
    #[OA\Property(description: 'qr_code_token', type: 'string')]
    public string $qr_code_token;
    #[OA\Property(description: 'status', type: 'string')]
    public string $status;
    #[OA\Property(description: 'checked_in_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $checked_in_at;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]|is_unique[tickets.uuid]',
            'booking_id' => 'required|is_natural_no_zero|is_not_unique[bookings.id]',
            'ticket_type_id' => 'required|is_natural_no_zero|is_not_unique[ticket_types.id]',
            'holder_name' => 'required|string|max_length[255]',
            'holder_email' => 'required|string|valid_email|max_length[255]',
            'qr_code_token' => 'permit_empty|string|max_length[255]|is_unique[tickets.qr_code_token]',
            'status' => 'required|string|max_length[255]',
            'checked_in_at' => 'permit_empty|valid_date',
        ];
    }

    protected function map(array $data): void
    {
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'booking_id' => $this->booking_id,
            'ticket_type_id' => $this->ticket_type_id,
            'holder_name' => $this->holder_name,
            'holder_email' => $this->holder_email,
            'qr_code_token' => $this->qr_code_token,
            'status' => $this->status,
            'checked_in_at' => $this->checked_in_at,
        ];
    }
}
