<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Content localization settings for the Event Domain.
 *
 * This is intentionally not a list of supported languages. The CMS language
 * catalog is dynamic; this only provides a safe fallback for legacy rows that
 * predate event_translations.
 */
class Localization extends BaseConfig
{
    public string $legacyFallbackLocale = 'es';

    public function __construct()
    {
        parent::__construct();

        $configured = trim((string) env('EVENT_LEGACY_FALLBACK_LOCALE', 'es'));
        if (preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/i', $configured) === 1) {
            $this->legacyFallbackLocale = strtolower(str_replace('_', '-', $configured));
        }
    }
}
