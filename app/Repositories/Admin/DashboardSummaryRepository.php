<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Interfaces\Admin\DashboardSummaryRepositoryInterface;
use App\Models\BookingModel;
use App\Models\EventModel;
use App\Models\EventReferenceModel;
use App\Models\EventTypeModel;
use App\Models\OccurrenceModel;
use App\Models\TicketModel;
use App\Models\TicketTypeModel;
use App\Models\VenueModel;
use CodeIgniter\Model;

final class DashboardSummaryRepository implements DashboardSummaryRepositoryInterface
{
    public function __construct(
        private readonly EventModel $events,
        private readonly EventTypeModel $eventTypes,
        private readonly VenueModel $venues,
        private readonly OccurrenceModel $occurrences,
        private readonly EventReferenceModel $eventReferences,
        private readonly TicketTypeModel $ticketTypes,
        private readonly BookingModel $bookings,
        private readonly TicketModel $tickets,
    ) {
    }

    /**
     * This is a fixed-size read model. It counts only resources visible to the
     * caller and fetches at most five recent rows per resource family.
     *
     * @param list<string> $permissions
     * @return array<string, mixed>
     */
    public function read(array $permissions): array
    {
        $resources = [
            'events' => [$this->events, 'event.events.read', 'id, title, updated_at', 'title'],
            'event_types' => [$this->eventTypes, 'event.event-types.read', 'id, name, slug, updated_at', 'name'],
            'venues' => [$this->venues, 'event.venues.read', 'id, name, slug, updated_at', 'name'],
            'occurrences' => [$this->occurrences, 'event.occurrences.read', 'id, status, updated_at', 'status'],
            'event_references' => [$this->eventReferences, 'event.event-references.read', 'id, name, updated_at', 'name'],
            'ticket_types' => [$this->ticketTypes, 'event.ticket-types.read', 'id, name, updated_at', 'name'],
            'bookings' => [$this->bookings, 'event.bookings.read', 'id, status, updated_at', 'status'],
            'tickets' => [$this->tickets, 'event.tickets.read', 'id, holder_name, status, updated_at', 'holder_name'],
        ];
        $counts = [];
        $activity = [];

        foreach ($resources as $type => [$model, $permission, $projection, $titleColumn]) {
            if (! in_array($permission, $permissions, true)) {
                continue;
            }

            $counts[$type] = (int) $model->countAllResults();
            if ($type !== 'event_references') {
                $activity = array_merge($activity, $this->recent($model, $type, $projection, $titleColumn));
            }
        }

        usort(
            $activity,
            static fn (array $left, array $right): int => strcmp(
                (string) ($right['updated_at'] ?? ''),
                (string) ($left['updated_at'] ?? '')
            )
        );

        return [
            'counts' => $counts,
            'recent_activity' => array_slice($activity, 0, 6),
        ];
    }

    /** @return list<array{type: string, id: int, title: string, updated_at: string}> */
    private function recent(Model $model, string $type, string $projection, string $titleColumn): array
    {
        $rows = $model
            ->select($projection)
            ->orderBy('updated_at', 'DESC')
            ->findAll(5);
        $items = [];

        foreach ($rows as $row) {
            if (! is_object($row)) {
                continue;
            }

            $items[] = [
                'type' => $type,
                'id' => (int) ($row->id ?? 0),
                'title' => trim((string) ($row->{$titleColumn} ?? '')),
                'updated_at' => (string) ($row->updated_at ?? ''),
            ];
        }

        return $items;
    }
}
