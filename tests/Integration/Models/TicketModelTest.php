<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\TicketModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for TicketModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class TicketModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new TicketModel();

        $this->assertSame('tickets', $model->getTable());
    }
}
