<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\EventReferenceEntity;
use App\Interfaces\Events\EventReferenceServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<EventReferenceEntity>
 */
class EventReferenceService extends BaseCrudService implements EventReferenceServiceInterface
{
    /**
     * @param RepositoryInterface<EventReferenceEntity> $eventReferenceRepository
     */
    public function __construct(
        RepositoryInterface $eventReferenceRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($eventReferenceRepository, $responseMapper);
    }

    /**
     * Idempotent create: importers are expected to safely re-link the same
     * external reference (`uq_event_external_reference` on event_id,
     * source_system, source_type, source_id, relation). Returning the
     * existing row instead of inserting keeps that contract without relying
     * on a caught duplicate-key database exception.
     */
    public function store(DataTransferObjectInterface $request, ?SecurityContext $context = null): DataTransferObjectInterface
    {
        $data = $request->toArray();

        /** @var EventReferenceEntity|null $existing */
        $existing = $this->repository->getModel()
            ->where('event_id', (int) ($data['event_id'] ?? 0))
            ->where('source_system', (string) ($data['source_system'] ?? ''))
            ->where('source_type', (string) ($data['source_type'] ?? ''))
            ->where('source_id', (string) ($data['source_id'] ?? ''))
            ->where('relation', (string) ($data['relation'] ?? ''))
            ->first();

        if ($existing !== null) {
            return $this->mapToResponse($existing);
        }

        return parent::store($request, $context);
    }
}
