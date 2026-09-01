<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\VenueModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for VenueModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class VenueModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new VenueModel();

        $this->assertSame('venues', $model->getTable());
    }
}
