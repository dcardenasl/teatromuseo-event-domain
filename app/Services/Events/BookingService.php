<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\BookingEntity;
use App\Interfaces\Events\BookingServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<BookingEntity>
 */
class BookingService extends BaseCrudService implements BookingServiceInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $pendingTicketData = [];

    /**
     * @param RepositoryInterface<BookingEntity> $bookingRepository
     */
    public function __construct(
        RepositoryInterface $bookingRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($bookingRepository, $responseMapper);
    }

    /**
     * Hook before creating a booking hold.
     * Validates availability, decrements inventory, and sets hold details.
     *
     * @param array<string, mixed> $data
     * @param SecurityContext|null $context
     * @return array<string, mixed>
     * @throws BadRequestException
     */
    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $ticketTypeId = (int) ($data['ticket_type_id'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 0);

        if ($ticketTypeId <= 0 || $quantity <= 0) {
            throw new BadRequestException(lang('Bookings.invalid_quantity'));
        }

        // Ticket types are always scoped to a concrete occurrence. This keeps
        // inventory and schedule ownership in one place.
        $ticketTypeModel = model(\App\Models\TicketTypeModel::class);
        $ticketType = $ticketTypeModel->find($ticketTypeId);

        if (!$ticketType) {
            throw new BadRequestException(lang('Bookings.ticket_type_not_found'));
        }

        if (empty($ticketType->occurrence_id)) {
            throw new BadRequestException(lang('Bookings.occurrence_not_found'));
        }

        $occurrence = model(\App\Models\OccurrenceModel::class)->find($ticketType->occurrence_id);

        if (! $occurrence) {
            throw new BadRequestException(lang('Bookings.occurrence_not_found'));
        }

        // Verify category and occurrence availability.
        if ($ticketType->available_spots < $quantity) {
            throw new BadRequestException(lang('Bookings.ticket_type_sold_out'));
        }

        if ($occurrence->available_spots < $quantity) {
            throw new BadRequestException(lang('Bookings.occurrence_sold_out'));
        }

        // Decrement both inventories atomically. Affected-row checks prevent
        // two concurrent holds from overselling the same stock.
        if (! $this->adjustInventory($ticketTypeId, (int) $occurrence->id, -$quantity)) {
            throw new BadRequestException(lang('Bookings.occurrence_sold_out'));
        }

        // Calculate backend total amount to prevent client tampering
        $totalAmount = (float) ($ticketType->price * $quantity);

        // Store ticket creation details for afterStore hook
        $this->pendingTicketData = [
            'ticket_type_id' => $ticketTypeId,
            'quantity'       => $quantity,
            'holder_name'    => $data['holder_name'] ?? null,
            'holder_email'   => $data['holder_email'] ?? null,
        ];

        // Override booking fields with backend values
        $data['total_amount'] = $totalAmount;
        $data['status']       = 'pending';
        $data['reserved_until'] = date('Y-m-d H:i:s', time() + 600); // 10 minute hold

        return $data;
    }

    /**
     * Hook after booking is created.
     * Generates associated individual Ticket records.
     *
     * @param object $entity
     * @param SecurityContext|null $context
     * @return void
     */
    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        /** @var \App\Entities\BookingEntity $entity */
        $pending = $this->pendingTicketData;

        if (!empty($pending)) {
            $ticketModel = model(\App\Models\TicketModel::class);
            $quantity = (int) $pending['quantity'];
            $ticketTypeId = (int) $pending['ticket_type_id'];

            // Fallback for holder contact info
            $holderName = $pending['holder_name'] ?: ($entity->guest_email ?: 'Guest');
            $holderEmail = $pending['holder_email'] ?: ($entity->guest_email ?: 'guest@example.com');

            for ($i = 0; $i < $quantity; $i++) {
                $ticketModel->insert([
                    'booking_id'     => $entity->id,
                    'ticket_type_id' => $ticketTypeId,
                    'holder_name'    => $holderName,
                    'holder_email'   => $holderEmail,
                    'status'         => 'pending',
                ]);
            }
        }
    }

    /**
     * Hook before updating a booking.
     * Handles status transitions: clears holds on confirm, releases spots on cancel/expire.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param SecurityContext|null $context
     * @return array<string, mixed>
     * @throws NotFoundException
     */
    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $booking = $this->repository->find($id);

        if (!$booking) {
            throw new NotFoundException(lang('Bookings.not_found'));
        }

        /** @var \App\Entities\BookingEntity $booking */
        $oldStatus = $booking->status;
        $newStatus = $data['status'] ?? null;

        if ($newStatus !== null && $oldStatus !== $newStatus) {
            $ticketModel = model(\App\Models\TicketModel::class);

            if ($newStatus === 'confirmed' && $oldStatus === 'pending') {
                // Confirming a pending hold: clear hold expiry
                $data['reserved_until'] = null;

                // Mark all tickets as active/confirmed
                $ticketModel->where('booking_id', $id)
                    ->set(['status' => 'confirmed'])
                    ->update();
            } elseif (in_array($newStatus, ['cancelled', 'expired'], true) && in_array($oldStatus, ['pending', 'confirmed'], true)) {
                // Cancelling or expiring a hold: release the spots back to inventory
                $tickets = $ticketModel->where('booking_id', $id)->findAll();

                /** @var list<\App\Entities\TicketEntity> $tickets */
                $counts = [];
                foreach ($tickets as $ticket) {
                    $counts[$ticket->ticket_type_id] = ($counts[$ticket->ticket_type_id] ?? 0) + 1;
                }

                $ticketTypeModel = model(\App\Models\TicketTypeModel::class);

                foreach ($counts as $ticketTypeId => $qty) {
                    $ticketType = $ticketTypeModel->find($ticketTypeId);
                    if ($ticketType) {
                        if (empty($ticketType->occurrence_id)) {
                            throw new BadRequestException(lang('Bookings.occurrence_not_found'));
                        }

                        // Release the spots back to both inventories.
                        $this->adjustInventory((int) $ticketTypeId, (int) $ticketType->occurrence_id, $qty);
                    }
                }

                // Update ticket statuses
                $ticketModel->where('booking_id', $id)
                    ->set(['status' => $newStatus])
                    ->update();

                // Clear reservation time
                $data['reserved_until'] = null;
            }
        }

        return $data;
    }

    /**
     * Adjusts a ticket type's and its occurrence's `available_spots` in one
     * atomic pair of updates. `$delta` is negative to hold spots (beforeStore)
     * and positive to release them back (beforeUpdate, on cancel/expire) — the
     * single call site both hooks used to duplicate inline (LAYER-03).
     *
     * @return bool true if both inventories were adjusted; false if a
     *               decrement could not be fully satisfied.
     */
    private function adjustInventory(int $ticketTypeId, int $occurrenceId, int $delta): bool
    {
        $ticketTypeUpdated = model(\App\Models\TicketTypeModel::class)->adjustAvailableSpots($ticketTypeId, $delta);
        $occurrenceUpdated = model(\App\Models\OccurrenceModel::class)->adjustAvailableSpots($occurrenceId, $delta);

        return $ticketTypeUpdated && $occurrenceUpdated;
    }
}
