<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\EventTypeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for EventTypeModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class EventTypeModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new EventTypeModel();

        $this->assertSame('event_types', $model->getTable());
    }
}
