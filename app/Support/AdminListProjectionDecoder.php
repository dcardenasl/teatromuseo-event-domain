<?php

declare(strict_types=1);

namespace App\Support;

final class AdminListProjectionDecoder
{
    /** @return list<array{locale: string, fields: array<string, string>}> */
    public static function translations(mixed $encoded): array
    {
        if (! is_string($encoded) || $encoded === '') {
            return [];
        }
        $rows = [];
        foreach (explode('|', $encoded) as $serialized) {
            $parts = explode(':', $serialized);
            if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '') {
                continue;
            }
            $value = self::decodeHex($parts[2]);
            if ($value === null) {
                continue;
            }
            $locale = (string) $parts[0];
            $rows[$locale] ??= ['locale' => $locale, 'fields' => []];
            $rows[$locale]['fields'][(string) $parts[1]] = $value;
        }
        return array_values($rows);
    }

    /** @return array<string, string> */
    public static function slugs(mixed $encoded): array
    {
        if (! is_string($encoded) || $encoded === '') {
            return [];
        }
        $slugs = [];
        foreach (explode('|', $encoded) as $serialized) {
            $parts = explode(':', $serialized);
            if (count($parts) !== 2 || $parts[0] === '') {
                continue;
            }
            $slug = self::decodeHex($parts[1]);
            if ($slug !== null && $slug !== '') {
                $slugs[(string) $parts[0]] = $slug;
            }
        }
        return $slugs;
    }

    private static function decodeHex(string $encoded): ?string
    {
        if ($encoded === '') {
            return '';
        }

        if (strlen($encoded) % 2 !== 0 || ! ctype_xdigit($encoded)) {
            return null;
        }

        $decoded = hex2bin($encoded);

        return $decoded === false ? null : $decoded;
    }
}
