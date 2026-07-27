<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Interfaces\Events\EventReferenceServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for EventReferenceService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class EventReferenceServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::eventReferenceService(false);

        $this->assertInstanceOf(EventReferenceServiceInterface::class, $service);
    }
}
