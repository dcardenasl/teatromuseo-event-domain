<?php

declare(strict_types=1);

namespace App\Interfaces\Events;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface EventServiceInterface extends CrudServiceContract
{
    // Declare resource-specific service methods here.
    // Implement them in EventService; until ready, throw:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
