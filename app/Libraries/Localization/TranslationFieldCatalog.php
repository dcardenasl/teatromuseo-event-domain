<?php

declare(strict_types=1);

namespace App\Libraries\Localization;

use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;

/**
 * Explicit content contract for resources owned by the Event Domain.
 *
 * Keeping this catalog centralized prevents arbitrary database fields from
 * becoming translatable and makes adding another resource a deliberate schema
 * decision.
 */
final class TranslationFieldCatalog
{
    /** @var array<string, list<string>> */
    private const FIELDS = [
        'event'      => ['title', 'description'],
        'event_type' => ['name'],
        'venue'      => ['name', 'description'],
        'ticket_type' => ['name'],
    ];

    /**
     * @return list<string>
     */
    public static function fields(string $resourceType): array
    {
        if (! isset(self::FIELDS[$resourceType])) {
            throw new BadRequestException('Unsupported translatable resource.');
        }

        return self::FIELDS[$resourceType];
    }

    public static function hasField(string $resourceType, string $field): bool
    {
        return in_array($field, self::fields($resourceType), true);
    }

    public static function fallbackLocale(): string
    {
        return config('Localization')->legacyFallbackLocale;
    }
}
