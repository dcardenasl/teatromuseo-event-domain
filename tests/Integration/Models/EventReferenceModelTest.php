<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\EventReferenceModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for EventReferenceModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class EventReferenceModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new EventReferenceModel();

        $this->assertSame('event_references', $model->getTable());
    }
}
