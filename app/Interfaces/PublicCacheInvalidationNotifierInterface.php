<?php

declare(strict_types=1);

namespace App\Interfaces;

interface PublicCacheInvalidationNotifierInterface
{
    /** @param list<string> $scopes */
    public function invalidate(array $scopes): void;
}
