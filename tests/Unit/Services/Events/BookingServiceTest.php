<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Interfaces\Events\BookingServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for BookingService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class BookingServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::bookingService(false);

        $this->assertInstanceOf(BookingServiceInterface::class, $service);
    }
}
