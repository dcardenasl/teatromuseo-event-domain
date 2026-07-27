<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\BookingModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for BookingModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class BookingModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new BookingModel();

        $this->assertSame('bookings', $model->getTable());
    }
}
