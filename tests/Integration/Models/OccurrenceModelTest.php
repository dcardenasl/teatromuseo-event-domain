<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\OccurrenceModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for OccurrenceModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class OccurrenceModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new OccurrenceModel();

        $this->assertSame('occurrences', $model->getTable());
    }
}
