<?php

declare(strict_types=1);

namespace Config;

trait EventsDomainServices
{
    /**
     * Shared per-request locale resolver.
     *
     * Both localization stores take the same instance so the Accept-Language /
     * locale header is parsed once per service graph.
     */
    public static function requestLocaleResolver(bool $getShared = true): \dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver
    {
        if ($getShared) {
            return static::getSharedInstance('requestLocaleResolver');
        }

        $request = \Config\Services::request(false);

        return new \dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver(
            $request instanceof \CodeIgniter\HTTP\IncomingRequest ? $request : null
        );
    }

    public static function localizedTranslationStore(bool $getShared = true): \dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore
    {
        if ($getShared) {
            return static::getSharedInstance('localizedTranslationStore');
        }

        return new \dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore(
            new \App\Models\EventTranslationModel(),
            static::requestLocaleResolver(),
            config('Localization'),
        );
    }

    public static function publicSlugStore(bool $getShared = true): \dcardenasl\Ci4ApiCore\Localization\PublicSlugStore
    {
        if ($getShared) {
            return static::getSharedInstance('publicSlugStore');
        }

        return new \dcardenasl\Ci4ApiCore\Localization\PublicSlugStore(
            new \App\Models\EventPublicSlugModel(),
            new \dcardenasl\Ci4ApiCore\Localization\SlugGenerator(),
            static::requestLocaleResolver(),
            config('Localization'),
        );
    }

    public static function eventResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('eventResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\EventResponseDTO::class);
    }
    public static function eventService(bool $getShared = true): \App\Interfaces\Events\EventServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('eventService');
        }
        return new \App\Services\Events\EventService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\EventModel::class)),
            static::eventResponseMapper(),
            static::localizedTranslationStore(),
            static::publicSlugStore(),
            new \App\Repositories\Events\OccurrenceRepository(new \App\Models\OccurrenceModel()),
            (string) env('EVENT_SCHEDULE_TIMEZONE', config('App')->eventScheduleTimezone),
        );
    }
    public static function ticketTypeResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('ticketTypeResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\TicketTypeResponseDTO::class);
    }
    public static function ticketTypeService(bool $getShared = true): \App\Interfaces\Events\TicketTypeServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('ticketTypeService');
        }
        return new \App\Services\Events\TicketTypeService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\TicketTypeModel::class)),
            static::ticketTypeResponseMapper(),
            static::localizedTranslationStore(),
        );
    }
    public static function bookingResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('bookingResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\BookingResponseDTO::class);
    }
    public static function bookingService(bool $getShared = true): \App\Interfaces\Events\BookingServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('bookingService');
        }
        return new \App\Services\Events\BookingService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BookingModel::class)), static::bookingResponseMapper());
    }
    public static function ticketResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('ticketResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\TicketResponseDTO::class);
    }
    public static function ticketService(bool $getShared = true): \App\Interfaces\Events\TicketServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('ticketService');
        }
        return new \App\Services\Events\TicketService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\TicketModel::class)), static::ticketResponseMapper());
    }
    public static function venueResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('venueResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\VenueResponseDTO::class);
    }
    public static function venueService(bool $getShared = true): \App\Interfaces\Events\VenueServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('venueService');
        }
        return new \App\Services\Events\VenueService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\VenueModel::class)),
            static::venueResponseMapper(),
            static::localizedTranslationStore(),
        );
    }
    public static function occurrenceResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('occurrenceResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\OccurrenceResponseDTO::class);
    }
    public static function occurrenceService(bool $getShared = true): \App\Interfaces\Events\OccurrenceServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('occurrenceService');
        }
        return new \App\Services\Events\OccurrenceService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\OccurrenceModel::class)),
            static::occurrenceResponseMapper(),
            (string) env('EVENT_SCHEDULE_TIMEZONE', config('App')->eventScheduleTimezone),
        );
    }
    public static function eventReferenceResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('eventReferenceResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\EventReferenceResponseDTO::class);
    }
    public static function eventReferenceService(bool $getShared = true): \App\Interfaces\Events\EventReferenceServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('eventReferenceService');
        }
        return new \App\Services\Events\EventReferenceService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\EventReferenceModel::class)), static::eventReferenceResponseMapper());
    }

    public static function fileUsageService(bool $getShared = true): \App\Services\Events\FileUsageService
    {
        if ($getShared) {
            return static::getSharedInstance('fileUsageService');
        }
        return new \App\Services\Events\FileUsageService(model(\App\Models\EventModel::class));
    }
    public static function eventTypeResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('eventTypeResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Events\EventTypeResponseDTO::class);
    }
    public static function eventTypeService(bool $getShared = true): \App\Interfaces\Events\EventTypeServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('eventTypeService');
        }
        return new \App\Services\Events\EventTypeService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\EventTypeModel::class)),
            static::eventTypeResponseMapper(),
            static::localizedTranslationStore(),
            static::publicSlugStore(),
            new \App\Models\EventPublicSlugModel(),
        );
    }
}
