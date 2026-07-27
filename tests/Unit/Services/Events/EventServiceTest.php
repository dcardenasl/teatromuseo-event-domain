<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Interfaces\Events\EventServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for EventService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class EventServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::eventService(false);

        $this->assertInstanceOf(EventServiceInterface::class, $service);
    }
}
