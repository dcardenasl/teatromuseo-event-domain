<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTO\Response\Admin\DashboardSummaryResponseDTO;
use App\Interfaces\Admin\DashboardSummaryRepositoryInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

final readonly class DashboardSummaryService
{
    public function __construct(
        private DashboardSummaryRepositoryInterface $repository,
    ) {
    }

    public function read(SecurityContext $context): DashboardSummaryResponseDTO
    {
        $permissions = $context->permissions;
        $permissionMap = [
            'events' => 'event.events.read',
            'event_types' => 'event.event-types.read',
            'venues' => 'event.venues.read',
            'occurrences' => 'event.occurrences.read',
            'event_references' => 'event.event-references.read',
            'ticket_types' => 'event.ticket-types.read',
            'bookings' => 'event.bookings.read',
            'tickets' => 'event.tickets.read',
        ];

        if (array_intersect(array_values($permissionMap), $permissions) === []) {
            return DashboardSummaryResponseDTO::fromArray([
                'version' => 1,
                'generated_at' => date(DATE_ATOM),
                'sections' => ['counts' => []],
            ]);
        }

        $source = $this->repository->read($permissions);
        $sourceCounts = is_array($source['counts'] ?? null) ? $source['counts'] : [];
        $counts = [];
        foreach ($permissionMap as $key => $permission) {
            if (in_array($permission, $permissions, true)) {
                $counts[$key] = (int) ($sourceCounts[$key] ?? 0);
            }
        }

        $activity = is_array($source['recent_activity'] ?? null) ? $source['recent_activity'] : [];
        $activity = array_values(array_filter(
            $activity,
            static fn (mixed $item): bool => is_array($item)
                && isset($permissionMap[(string) ($item['type'] ?? '')])
                && in_array($permissionMap[(string) $item['type']], $permissions, true)
        ));

        return DashboardSummaryResponseDTO::fromArray([
            'version' => 1,
            'generated_at' => date(DATE_ATOM),
            'sections' => [
                'counts' => $counts,
                'recent_activity' => $activity,
            ],
        ]);
    }
}
