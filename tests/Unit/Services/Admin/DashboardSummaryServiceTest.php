<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Interfaces\Admin\DashboardSummaryRepositoryInterface;
use App\Services\Admin\DashboardSummaryService;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

/** @internal */
final class DashboardSummaryServiceTest extends CIUnitTestCase
{
    public function testSummaryIsPermissionAware(): void
    {
        $repository = $this->createMock(DashboardSummaryRepositoryInterface::class);
        $repository->expects($this->once())->method('read')->with([
            'event.events.read',
        ])->willReturn([
            'counts' => ['events' => 3, 'bookings' => 10],
            'recent_activity' => [['type' => 'events', 'id' => 1, 'title' => 'Opening']],
        ]);

        $result = (new DashboardSummaryService($repository))->read(
            new SecurityContext(7, [], ['event.events.read'])
        );

        $this->assertSame(['events' => 3], $result->sections['counts']);
        $this->assertSame([['type' => 'events', 'id' => 1, 'title' => 'Opening']], $result->sections['recent_activity']);
    }

    public function testNoEventPermissionDoesNotReadRepository(): void
    {
        $repository = $this->createMock(DashboardSummaryRepositoryInterface::class);
        $repository->expects($this->never())->method('read');

        $result = (new DashboardSummaryService($repository))->read(
            new SecurityContext(7, [], ['users.read'])
        );

        $this->assertSame(['counts' => []], $result->sections);
    }
}
