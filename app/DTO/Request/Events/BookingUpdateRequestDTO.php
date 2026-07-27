<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BookingUpdateRequest')]
readonly class BookingUpdateRequestDTO extends BaseRequestDTO
{
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

    protected function map(array $data): void
    {
        $this->uuid = $data['uuid'] ?? null;
        $this->user_id = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $this->guest_email = $data['guest_email'] ?? null;
        $this->total_amount = isset($data['total_amount']) ? (float) $data['total_amount'] : null;
        $this->status = $data['status'] ?? null;
        $this->reserved_until = $data['reserved_until'] ?? null;
    }

    public function toArray(): array
    {
        return array_filter([
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'guest_email' => $this->guest_email,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'reserved_until' => $this->reserved_until,
        ], fn ($v) => $v !== null);
    }
}
