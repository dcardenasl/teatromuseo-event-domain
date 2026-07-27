<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TicketResponse',
    title: 'Ticket Response',
    required: ["id","uuid","booking_id","ticket_type_id","holder_name","holder_email","qr_code_token","status"]
)]
final readonly class TicketResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'uuid', type: 'string')]
        public string $uuid,
        #[OA\Property(description: 'booking_id', type: 'integer')]
        public int $booking_id,
        #[OA\Property(description: 'ticket_type_id', type: 'integer')]
        public int $ticket_type_id,
        #[OA\Property(description: 'holder_name', type: 'string')]
        public string $holder_name,
        #[OA\Property(description: 'holder_email', type: 'string', format: 'email')]
        public string $holder_email,
        #[OA\Property(description: 'qr_code_token', type: 'string')]
        public string $qr_code_token,
        #[OA\Property(description: 'status', type: 'string')]
        public string $status,
        #[OA\Property(description: 'checked_in_at', type: 'string', format: 'date-time', nullable: true)]
        public ?string $checked_in_at,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            uuid: (string) ($data['uuid'] ?? ''),
            booking_id: (int) ($data['booking_id'] ?? 0),
            ticket_type_id: (int) ($data['ticket_type_id'] ?? 0),
            holder_name: (string) ($data['holder_name'] ?? ''),
            holder_email: (string) ($data['holder_email'] ?? ''),
            qr_code_token: (string) ($data['qr_code_token'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            checked_in_at: $data['checked_in_at'] ?? null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'booking_id' => $this->booking_id,
            'ticket_type_id' => $this->ticket_type_id,
            'holder_name' => $this->holder_name,
            'holder_email' => $this->holder_email,
            'qr_code_token' => $this->qr_code_token,
            'status' => $this->status,
            'checked_in_at' => $this->checked_in_at,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
