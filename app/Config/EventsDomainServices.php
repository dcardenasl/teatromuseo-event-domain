<?php

declare(strict_types=1);

namespace Config;

trait EventsDomainServices
{
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
        return new \App\Services\Events\EventService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\EventModel::class)), static::eventResponseMapper());
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
        return new \App\Services\Events\TicketTypeService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\TicketTypeModel::class)), static::ticketTypeResponseMapper());
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
}
