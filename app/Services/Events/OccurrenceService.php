<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\OccurrenceEntity;
use App\Interfaces\Events\OccurrenceServiceInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use DateTimeImmutable;
use DateTimeZone;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<OccurrenceEntity>
 */
class OccurrenceService extends BaseCrudService implements OccurrenceServiceInterface
{
    private DateTimeZone $scheduleTimezone;

    /**
     * @param RepositoryInterface<OccurrenceEntity> $occurrenceRepository
     */
    public function __construct(
        RepositoryInterface $occurrenceRepository,
        ResponseMapperInterface $responseMapper,
        string $scheduleTimezone,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
    ) {
        parent::__construct($occurrenceRepository, $responseMapper);
        $this->scheduleTimezone = new DateTimeZone($scheduleTimezone);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function beforeStore(array $data, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): array
    {
        $data['start_time'] = $this->normalize($data['start_time'] ?? null);
        $data['end_time'] = $this->normalize($data['end_time'] ?? null);
        $this->assertChronology($data['start_time'], $data['end_time']);

        return $data;
    }

    protected function afterStore(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->cacheInvalidator->invalidate(['events']);
    }

    protected function afterUpdate(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->cacheInvalidator->invalidate(['events']);
    }

    protected function afterDelete(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['events']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function beforeUpdate(int $id, array $data, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): array
    {
        $existing = $this->repository->find($id);
        if ($existing === null) {
            return $data;
        }

        $start = array_key_exists('start_time', $data)
            ? $this->normalize($data['start_time'])
            : (string) ($existing->start_time ?? '');
        $end = array_key_exists('end_time', $data)
            ? $this->normalize($data['end_time'])
            : (string) ($existing->end_time ?? '');
        $this->assertChronology($start, $end);

        if (array_key_exists('start_time', $data)) {
            $data['start_time'] = $start;
        }
        if (array_key_exists('end_time', $data)) {
            $data['end_time'] = $end;
        }

        return $data;
    }

    private function normalize(mixed $value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            throw new BadRequestException(lang('Occurrences.invalid_schedule_required'));
        }

        try {
            return (new DateTimeImmutable($raw, $this->scheduleTimezone))->setTimezone($this->scheduleTimezone)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            throw new BadRequestException(lang('Occurrences.invalid_schedule_format'));
        }
    }

    private function assertChronology(string $start, string $end): void
    {
        if (new DateTimeImmutable($end, $this->scheduleTimezone) <= new DateTimeImmutable($start, $this->scheduleTimezone)) {
            throw new BadRequestException(lang('Occurrences.invalid_schedule_order'));
        }
    }

    // Custom methods declared in OccurrenceServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
