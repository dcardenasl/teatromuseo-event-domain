<?php

declare(strict_types=1);

namespace App\DTO\Response\Events;

use App\Traits\DTO\NormalizesResponseTimestamps;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BookingResponse',
    title: 'Booking Response',
    required: ["id","uuid","total_amount","status"]
)]
final readonly class BookingResponseDTO implements DataTransferObjectInterface
{
    use NormalizesResponseTimestamps;

    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'uuid', type: 'string')]
        public string $uuid,
        #[OA\Property(description: 'user_id', type: 'integer', nullable: true)]
        public ?int $user_id,
        #[OA\Property(description: 'guest_email', type: 'string', format: 'email', nullable: true)]
        public ?string $guest_email,
        #[OA\Property(description: 'total_amount', type: 'number', format: 'float')]
        public float $total_amount,
        #[OA\Property(description: 'status', type: 'string')]
        public string $status,
        #[OA\Property(description: 'reserved_until', type: 'string', format: 'date-time', nullable: true)]
        public ?string $reserved_until,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            uuid: (string) ($data['uuid'] ?? ''),
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            guest_email: $data['guest_email'] ?? null,
            total_amount: (float) ($data['total_amount'] ?? 0),
            status: (string) ($data['status'] ?? ''),
            reserved_until: $data['reserved_until'] ?? null,
            createdAt: self::normalizeResponseTimestamp($data['created_at'] ?? null),
            updatedAt: self::normalizeResponseTimestamp($data['updated_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'guest_email' => $this->guest_email,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'reserved_until' => $this->reserved_until,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
