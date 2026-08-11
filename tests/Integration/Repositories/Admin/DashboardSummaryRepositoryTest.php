<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories\Admin;

use App\Models\BookingModel;
use App\Models\EventModel;
use App\Models\EventReferenceModel;
use App\Models\EventTypeModel;
use App\Models\OccurrenceModel;
use App\Models\TicketModel;
use App\Models\TicketTypeModel;
use App\Models\VenueModel;
use App\Repositories\Admin\DashboardSummaryRepository;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/** @internal */
final class DashboardSummaryRepositoryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testReadUsesOnlyColumnsPresentInEventTables(): void
    {
        $repository = new DashboardSummaryRepository(
            new EventModel(),
            new EventTypeModel(),
            new VenueModel(),
            new OccurrenceModel(),
            new EventReferenceModel(),
            new TicketTypeModel(),
            new BookingModel(),
            new TicketModel(),
        );

        $result = $repository->read([
            'event.events.read',
            'event.event-types.read',
            'event.venues.read',
            'event.occurrences.read',
            'event.event-references.read',
            'event.ticket-types.read',
            'event.bookings.read',
            'event.tickets.read',
        ]);

        $this->assertSame([
            'events' => 0,
            'event_types' => 5,
            'venues' => 0,
            'occurrences' => 0,
            'event_references' => 0,
            'ticket_types' => 0,
            'bookings' => 0,
            'tickets' => 0,
        ], $result['counts']);
        $this->assertCount(5, $result['recent_activity']);
        $this->assertSame(
            ['event_types', 'event_types', 'event_types', 'event_types', 'event_types'],
            array_column($result['recent_activity'], 'type')
        );
    }
}
