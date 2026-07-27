<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Interfaces\Events\TicketTypeServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for TicketTypeService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class TicketTypeServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::ticketTypeService(false);

        $this->assertInstanceOf(TicketTypeServiceInterface::class, $service);
    }
}
