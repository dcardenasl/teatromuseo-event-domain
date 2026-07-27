<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TicketUpdateRequest')]
readonly class TicketUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'uuid', type: 'string', nullable: true)]
    public ?string $uuid;
    #[OA\Property(description: 'booking_id', type: 'integer', nullable: true)]
    public ?int $booking_id;
    #[OA\Property(description: 'ticket_type_id', type: 'integer', nullable: true)]
    public ?int $ticket_type_id;
    #[OA\Property(description: 'holder_name', type: 'string', nullable: true)]
    public ?string $holder_name;
    #[OA\Property(description: 'holder_email', type: 'string', format: 'email', nullable: true)]
    public ?string $holder_email;
    #[OA\Property(description: 'qr_code_token', type: 'string', nullable: true)]
    public ?string $qr_code_token;
    #[OA\Property(description: 'status', type: 'string', nullable: true)]
    public ?string $status;
    #[OA\Property(description: 'checked_in_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $checked_in_at;

    public function rules(): array
    {
        return [
            'uuid' => 'permit_empty|string|max_length[255]',
            'booking_id' => 'permit_empty|is_natural_no_zero|is_not_unique[bookings.id]',
            'ticket_type_id' => 'permit_empty|is_natural_no_zero|is_not_unique[ticket_types.id]',
            'holder_name' => 'permit_empty|string|max_length[255]',
            'holder_email' => 'permit_empty|string|valid_email|max_length[255]',
            'qr_code_token' => 'permit_empty|string|max_length[255]',
            'status' => 'permit_empty|string|max_length[255]',
            'checked_in_at' => 'permit_empty|valid_date',
        ];
    }

    protected function map(array $data): void
    {
        $this->uuid = $data['uuid'] ?? null;
        $this->booking_id = isset($data['booking_id']) ? (int) $data['booking_id'] : null;
        $this->ticket_type_id = isset($data['ticket_type_id']) ? (int) $data['ticket_type_id'] : null;
        $this->holder_name = $data['holder_name'] ?? null;
        $this->holder_email = $data['holder_email'] ?? null;
        $this->qr_code_token = $data['qr_code_token'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->checked_in_at = $data['checked_in_at'] ?? null;
    }

    public function toArray(): array
    {
        return array_filter([
            'uuid' => $this->uuid,
            'booking_id' => $this->booking_id,
            'ticket_type_id' => $this->ticket_type_id,
            'holder_name' => $this->holder_name,
            'holder_email' => $this->holder_email,
            'qr_code_token' => $this->qr_code_token,
            'status' => $this->status,
            'checked_in_at' => $this->checked_in_at,
        ], fn ($v) => $v !== null);
    }
}
