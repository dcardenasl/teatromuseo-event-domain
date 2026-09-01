<?php

declare(strict_types=1);

namespace App\Jobs;

use Config\Services;
use dcardenasl\Ci4ApiCore\Queue\Job;

final class CacheInvalidationOutboxJob extends Job
{
    public function handle(): void
    {
        Services::cacheInvalidationOutboxDispatcher(false)->dispatch(20);
    }
}
