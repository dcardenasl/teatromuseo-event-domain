<?php

declare(strict_types=1);

namespace App\Libraries\Localization;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Parses the Accept-Language header into an ordered locale preference list.
 *
 * Shared by the translation store and the public slug store so both resolve
 * the request locale with identical semantics.
 */
final class RequestLocaleResolver
{
    public function __construct(private ?IncomingRequest $request = null)
    {
    }

    /**
     * @return list<string>
     */
    public function requestedLocales(): array
    {
        $header = $this->request?->getHeaderLine('Accept-Language') ?? '';

        $weightedLocales = [];
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            $quality = 1.0;
            if (preg_match('/;q=([0-9.]+)/i', $part, $matches) === 1) {
                $quality = (float) $matches[1];
            }

            $locale = trim((string) preg_replace('/;q=[0-9.]+/i', '', $part));
            if ($locale === '' || preg_match('/^[a-z]{2,3}(?:[-_][a-z0-9]{2,8})*$/i', $locale) !== 1) {
                continue;
            }

            $normalizedLocale = strtolower(str_replace('_', '-', $locale));
            $weightedLocales[$normalizedLocale] = max($quality, $weightedLocales[$normalizedLocale] ?? 0.0);
        }

        arsort($weightedLocales, SORT_NUMERIC);

        return array_keys($weightedLocales);
    }
}
