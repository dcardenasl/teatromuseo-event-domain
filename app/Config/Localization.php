<?php

declare(strict_types=1);

namespace Config;

use dcardenasl\Ci4ApiCore\Config\Localization as BaseLocalization;

/**
 * Content localization registry for the Event Domain.
 *
 * `$translatableFields` is the explicit content contract for resources owned by
 * this domain, and doubles as the allow-list that stops arbitrary database
 * columns from becoming translatable — adding a resource stays a deliberate
 * schema decision. It replaces the former `App\Libraries\Localization\
 * TranslationFieldCatalog`, whose `fields()`/`hasField()` contract the core
 * config now provides verbatim.
 *
 * `$legacyFallbackLocale` is intentionally not a list of supported languages:
 * the CMS language catalog is dynamic, and this only provides a safe fallback
 * for legacy rows that predate `event_translations`. Override it per environment
 * with `LOCALIZATION_LEGACY_FALLBACK_LOCALE`.
 */
class Localization extends BaseLocalization
{
    /** @var array<string, list<string>> */
    public array $translatableFields = [
        'event'       => ['title', 'description'],
        'event_type'  => ['name'],
        'venue'       => ['name', 'description'],
        'ticket_type' => ['name'],
    ];

    public string $legacyFallbackLocale = 'es';
}
