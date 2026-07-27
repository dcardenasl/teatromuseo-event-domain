<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\DTO\Request\Events\BookingCreateRequestDTO;
use App\DTO\Request\Events\BookingUpdateRequestDTO;
use App\Models\EventModel;
use App\Models\TicketModel;
use App\Models\TicketTypeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;

/**
 * Integration tests for BookingService transactional business logic.
 *
 * @internal
 */
final class BookingServiceIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $eventId;
    private int $ticketTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test event
        $eventModel = model(EventModel::class);
        $this->eventId = (int) $eventModel->insert([
            'uuid'            => \dcardenasl\Ci4ApiCore\Security\Token::generateUuid(),
            'title'           => 'Awesome Rock Concert',
            'event_type'      => 'function',
            'description'     => 'An unforgettable rock music event.',
            'start_time'      => '2026-08-15 20:00:00',
            'end_time'        => '2026-08-15 23:00:00',
            'venue'           => 'Rock Arena Stadium',
            'capacity'        => 100,
            'available_spots' => 10,
            'status'          => 'active',
        ]);

        // Create a test ticket type linked to that event
        $ticketTypeModel = model(TicketTypeModel::class);
        $this->ticketTypeId = (int) $ticketTypeModel->insert([
            'event_id'        => $this->eventId,
            'name'            => 'VIP Front Row',
            'price'           => 120.00,
            'capacity'        => 50,
            'available_spots' => 5,
            'sales_start'     => '2026-01-01 00:00:00',
            'sales_end'       => '2026-08-14 23:59:59',
        ]);
    }

    public function testBookingHoldReservationLifecycle(): void
    {
        $bookingService = Services::bookingService();
        $dtoFactory = Services::requestDtoFactory();

        // 1. Create a hold for 2 tickets
        $createDto = $dtoFactory->make(BookingCreateRequestDTO::class, [
            'ticket_type_id' => $this->ticketTypeId,
            'quantity'       => 2,
            'guest_email'    => 'vip-fan@example.com',
            'holder_name'    => 'John Doe',
            'holder_email'   => 'john.doe@example.com',
            'total_amount'   => 0.00, // overridden by service
            'status'         => 'draft', // overridden by service
        ]);

        $bookingResponse = $bookingService->store($createDto);
        $bookingData = $bookingResponse->toArray();

        // Asserts on booking response and state
        $this->assertSame('pending', $bookingData['status']);
        $this->assertSame(240.00, (float) $bookingData['total_amount']);
        $this->assertNotEmpty($bookingData['reserved_until']);

        $bookingId = (int) $bookingData['id'];

        // Verify inventory decrement
        $eventModel = model(EventModel::class);
        $ticketTypeModel = model(TicketTypeModel::class);

        $this->assertSame(8, (int) $eventModel->find($this->eventId)->available_spots);
        $this->assertSame(3, (int) $ticketTypeModel->find($this->ticketTypeId)->available_spots);

        // Verify ticket creation
        $ticketModel = model(TicketModel::class);
        $tickets = $ticketModel->where('booking_id', $bookingId)->findAll();
        $this->assertCount(2, $tickets);

        foreach ($tickets as $ticket) {
            $this->assertSame('pending', $ticket->status);
            $this->assertSame('john.doe@example.com', $ticket->holder_email);
            $this->assertNotEmpty($ticket->uuid);
            $this->assertNotEmpty($ticket->qr_code_token);
        }

        // 2. Transition booking hold to confirmed
        $updateDto = $dtoFactory->make(BookingUpdateRequestDTO::class, [
            'status' => 'confirmed'
        ]);

        $bookingResponse = $bookingService->update($bookingId, $updateDto);
        $bookingData = $bookingResponse->toArray();

        $this->assertSame('confirmed', $bookingData['status']);
        $this->assertEmpty($bookingData['reserved_until']);

        // Verify tickets status transitions to confirmed
        $tickets = $ticketModel->where('booking_id', $bookingId)->findAll();
        foreach ($tickets as $ticket) {
            $this->assertSame('confirmed', $ticket->status);
        }

        // Verify spots stay decremented
        $this->assertSame(8, (int) $eventModel->find($this->eventId)->available_spots);
        $this->assertSame(3, (int) $ticketTypeModel->find($this->ticketTypeId)->available_spots);

        // 3. Cancel the confirmed booking
        $cancelDto = $dtoFactory->make(BookingUpdateRequestDTO::class, [
            'status' => 'cancelled'
        ]);

        $bookingResponse = $bookingService->update($bookingId, $cancelDto);
        $bookingData = $bookingResponse->toArray();

        $this->assertSame('cancelled', $bookingData['status']);

        // Verify tickets status transitions to cancelled
        $tickets = $ticketModel->where('booking_id', $bookingId)->findAll();
        foreach ($tickets as $ticket) {
            $this->assertSame('cancelled', $ticket->status);
        }

        // Verify inventory incremented back!
        $this->assertSame(10, (int) $eventModel->find($this->eventId)->available_spots);
        $this->assertSame(5, (int) $ticketTypeModel->find($this->ticketTypeId)->available_spots);

        // 4. Double cancellation check (must NOT refund again)
        $bookingService->update($bookingId, $cancelDto);
        $this->assertSame(10, (int) $eventModel->find($this->eventId)->available_spots);
        $this->assertSame(5, (int) $ticketTypeModel->find($this->ticketTypeId)->available_spots);
    }

    public function testBookingThrowsSoldOutException(): void
    {
        $bookingService = Services::bookingService();
        $dtoFactory = Services::requestDtoFactory();

        // Category only has 5 spots. Try to reserve 6.
        $createDto = $dtoFactory->make(BookingCreateRequestDTO::class, [
            'ticket_type_id' => $this->ticketTypeId,
            'quantity'       => 6,
            'guest_email'    => 'greedy-buyer@example.com',
            'holder_name'    => 'Greedy',
            'holder_email'   => 'greedy@example.com',
            'total_amount'   => 0.00,
            'status'         => 'draft',
        ]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(lang('Bookings.ticket_type_sold_out'));

        $bookingService->store($createDto);
    }
}
