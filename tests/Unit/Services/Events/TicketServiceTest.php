<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Interfaces\Events\TicketServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for TicketService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class TicketServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::ticketService(false);

        $this->assertInstanceOf(TicketServiceInterface::class, $service);
    }
}
