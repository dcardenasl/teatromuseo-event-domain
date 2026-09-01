<?php

declare(strict_types=1);

namespace App\Libraries\PublicCache;

use App\Interfaces\PublicCacheInvalidationNotifierInterface;

final class PublicCacheInvalidationNotifier implements PublicCacheInvalidationNotifierInterface
{
    public function __construct(private readonly CacheInvalidationOutbox $outbox)
    {
    }

    /** @param list<string> $scopes */
    public function invalidate(array $scopes): void
    {
        $this->outbox->append($scopes);
    }
}
