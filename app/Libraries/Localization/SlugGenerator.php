<?php

declare(strict_types=1);

namespace App\Libraries\Localization;

/**
 * Deterministic, transliterating slugifier for public routing slugs.
 *
 * Kept free of persistence concerns; uniqueness within a locale is the
 * responsibility of PublicSlugStore, which owns the database lookups.
 */
final class SlugGenerator
{
    public function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }

        // Strip diacritics via Unicode decomposition (platform-independent),
        // falling back to iconv when the intl extension is unavailable.
        // iconv alone is NOT enough: macOS libiconv transliterates "ó" to "'o".
        $ascii = null;
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_D);
            if (is_string($normalized)) {
                $ascii = preg_replace('/\p{Mn}+/u', '', $normalized);
            }
        }

        if (! is_string($ascii) || $ascii === '') {
            $ascii = self::fallbackTransliterate($value);
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '-', $ascii);
        if (! is_string($slug)) {
            return '';
        }

        return trim(mb_strtolower($slug), '-');
    }

    private static function fallbackTransliterate(string $value): string
    {
        return strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ç' => 'c', 'Ç' => 'C', 'ý' => 'y', 'ÿ' => 'y', 'Ý' => 'Y',
            'ß' => 'ss', 'œ' => 'oe', 'Œ' => 'OE', 'æ' => 'ae', 'Æ' => 'AE', 'ø' => 'o', 'Ø' => 'O',
        ]);
    }

    /**
     * Append a numeric suffix until the candidate passes the given
     * availability check. The base slug itself is tried first.
     *
     * @param callable(string): bool $isAvailable
     */
    public function uniquify(string $baseSlug, callable $isAvailable): string
    {
        if ($baseSlug === '') {
            return '';
        }

        if ($isAvailable($baseSlug)) {
            return $baseSlug;
        }

        for ($suffix = 2; $suffix < 1000; $suffix++) {
            $candidate = $baseSlug . '-' . $suffix;
            if ($isAvailable($candidate)) {
                return $candidate;
            }
        }

        // Pathological collision volume — fall back to a random suffix so the
        // write still succeeds instead of looping forever.
        return $baseSlug . '-' . bin2hex(random_bytes(4));
    }
}
