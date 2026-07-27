<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\TicketTypeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for TicketTypeModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class TicketTypeModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new TicketTypeModel();

        $this->assertSame('ticket_types', $model->getTable());
    }
}
