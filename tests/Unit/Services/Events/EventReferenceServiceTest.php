<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\DTO\Request\Events\EventReferenceCreateRequestDTO;
use App\Interfaces\Events\EventReferenceServiceInterface;
use App\Models\EventModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

/**
 * Smoke tests for EventReferenceService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class EventReferenceServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::eventReferenceService(false);

        $this->assertInstanceOf(EventReferenceServiceInterface::class, $service);
    }

    public function testStoreIsIdempotentForTheSameExternalReference(): void
    {
        $eventModel = model(EventModel::class);
        $eventId = (int) $eventModel->insert([
            'title' => 'Idempotency fixture event',
            'event_type' => 'function',
            'description' => 'Fixture used to verify event_references idempotency.',
        ]);

        $dtoFactory = Services::requestDtoFactory();
        $payload = [
            'event_id' => $eventId,
            'source_system' => 'cms-domain',
            'source_type' => 'entry',
            'source_id' => '42',
            'relation' => 'editorial_work',
        ];

        $service = Services::eventReferenceService(false);

        $first = $service->store($dtoFactory->make(EventReferenceCreateRequestDTO::class, $payload));
        $second = $service->store($dtoFactory->make(EventReferenceCreateRequestDTO::class, $payload));

        $this->assertSame($first->toArray()['id'], $second->toArray()['id']);
        $this->seeNumRecords(1, 'event_references', ['event_id' => $eventId]);
    }
}
