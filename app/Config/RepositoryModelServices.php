<?php

declare(strict_types=1);

namespace Config;

trait RepositoryModelServices
{
    public static function dashboardSummaryRepository(bool $getShared = true): \App\Interfaces\Admin\DashboardSummaryRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('dashboardSummaryRepository');
        }

        return new \App\Repositories\Admin\DashboardSummaryRepository(
            new \App\Models\EventModel(),
            new \App\Models\EventTypeModel(),
            new \App\Models\VenueModel(),
            new \App\Models\OccurrenceModel(),
            new \App\Models\EventReferenceModel(),
            new \App\Models\TicketTypeModel(),
            new \App\Models\BookingModel(),
            new \App\Models\TicketModel(),
        );
    }

    public static function auditRepository(bool $getShared = true): \dcardenasl\Ci4ApiCore\Repositories\AuditRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('auditRepository');
        }

        return new \App\Repositories\System\AuditRepository(model(\App\Models\AuditLogModel::class));
    }
}
