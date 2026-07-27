<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Interfaces\Events\OccurrenceServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for OccurrenceService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class OccurrenceServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::occurrenceService(false);

        $this->assertInstanceOf(OccurrenceServiceInterface::class, $service);
    }
}
