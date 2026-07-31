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

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * checked_in_at is the only nullable column on tickets — it preserves
     * an explicit null so it reaches toArray() and actually clears the
     * column (e.g. reverting an accidental check-in). Every other field is
     * NOT NULL, so an explicit null there is treated the same as omitting
     * the field — the bug this fixes is array_filter() silently dropping
     * every null, which made it impossible to ever clear the one field
     * that can be.
     */
    protected function map(array $data): void
    {
        $this->uuid = array_key_exists('uuid', $data) && $data['uuid'] !== null ? (string) $data['uuid'] : null;
        $this->booking_id = array_key_exists('booking_id', $data) && $data['booking_id'] !== null && $data['booking_id'] !== '' ? (int) $data['booking_id'] : null;
        $this->ticket_type_id = array_key_exists('ticket_type_id', $data) && $data['ticket_type_id'] !== null && $data['ticket_type_id'] !== '' ? (int) $data['ticket_type_id'] : null;
        $this->holder_name = array_key_exists('holder_name', $data) && $data['holder_name'] !== null ? (string) $data['holder_name'] : null;
        $this->holder_email = array_key_exists('holder_email', $data) && $data['holder_email'] !== null ? (string) $data['holder_email'] : null;
        $this->qr_code_token = array_key_exists('qr_code_token', $data) && $data['qr_code_token'] !== null ? (string) $data['qr_code_token'] : null;
        $this->status = array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : null;
        $this->checked_in_at = array_key_exists('checked_in_at', $data) && $data['checked_in_at'] !== null ? (string) $data['checked_in_at'] : null;

        $mappedFields = [];
        if ($this->uuid !== null) {
            $mappedFields['uuid'] = $this->uuid;
        }
        if ($this->booking_id !== null) {
            $mappedFields['booking_id'] = $this->booking_id;
        }
        if ($this->ticket_type_id !== null) {
            $mappedFields['ticket_type_id'] = $this->ticket_type_id;
        }
        if ($this->holder_name !== null) {
            $mappedFields['holder_name'] = $this->holder_name;
        }
        if ($this->holder_email !== null) {
            $mappedFields['holder_email'] = $this->holder_email;
        }
        if ($this->qr_code_token !== null) {
            $mappedFields['qr_code_token'] = $this->qr_code_token;
        }
        if ($this->status !== null) {
            $mappedFields['status'] = $this->status;
        }
        if (array_key_exists('checked_in_at', $data)) {
            $mappedFields['checked_in_at'] = $this->checked_in_at;
        }

        $this->mappedFields = $mappedFields;
    }

    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
