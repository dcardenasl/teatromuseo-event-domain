<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories\Admin;

use App\Models\EventModel;
use App\Models\EventTypeModel;
use App\Repositories\Events\EventListRepository;
use App\Repositories\Events\EventTypeListRepository;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/** @internal */
final class AdminListProjectionRepositoryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testEventAdminListProjectionsExecuteAsSinglePaginatedReads(): void
    {
        $repositories = [
            new EventListRepository(new EventModel(), $this->db),
            new EventTypeListRepository(new EventTypeModel(), $this->db),
        ];

        foreach ($repositories as $repository) {
            $result = $repository->paginateAdminList([], 1, 20);

            $this->assertIsArray($result['data']);
            $this->assertGreaterThanOrEqual(count($result['data']), $result['total']);
            $this->assertSame(1, $result['page']);
            $this->assertSame(20, $result['per_page']);
        }
    }
}
