<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Interfaces\Events\VenueServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for VenueService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class VenueServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::venueService(false);

        $this->assertInstanceOf(VenueServiceInterface::class, $service);
    }
}
