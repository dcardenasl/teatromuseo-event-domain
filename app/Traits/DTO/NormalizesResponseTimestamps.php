<?php

declare(strict_types=1);

namespace App\Traits\DTO;

trait NormalizesResponseTimestamps
{
    protected static function normalizeResponseTimestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
