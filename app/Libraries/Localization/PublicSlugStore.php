<?php

declare(strict_types=1);

namespace App\Libraries\Localization;

use App\Models\EventPublicSlugModel;

/**
 * Persistence and resolution boundary for public routing slugs.
 *
 * Contract:
 * - One slug per (resource, locale); a slug is unique within its locale.
 * - Slugs are generated once from the localized title and stay stable when
 *   the title later changes, so published URLs never break.
 * - A manual slug submitted by an editor always wins and is re-uniquified.
 */
final class PublicSlugStore
{
    public function __construct(
        private EventPublicSlugModel $model,
        private SlugGenerator $generator,
        private RequestLocaleResolver $localeResolver,
    ) {
    }

    /**
     * Ensure every locale that has a title carries a slug. Existing slugs are
     * preserved (URL stability); manual slugs overwrite.
     *
     * @param array<string, string> $titlesByLocale locale => localized title
     * @param array<string, string> $manualSlugs    locale => editor-provided slug
     */
    public function syncForResource(string $resourceType, int $resourceId, array $titlesByLocale, array $manualSlugs = []): void
    {
        if ($resourceId < 1) {
            return;
        }

        $existing = $this->slugsForResource($resourceType, $resourceId);

        foreach ($manualSlugs as $locale => $manualSlug) {
            $locale = $this->normalizeLocale($locale);
            $slug = $this->generator->slugify($manualSlug);
            if ($locale === '' || $slug === '' || ($existing[$locale] ?? null) === $slug) {
                continue;
            }

            $slug = $this->generator->uniquify(
                $slug,
                fn (string $candidate): bool => $this->isAvailable($resourceType, $locale, $candidate, $resourceId)
            );
            $this->upsert($resourceType, $resourceId, $locale, $slug);
            $existing[$locale] = $slug;
        }

        foreach ($titlesByLocale as $locale => $title) {
            $locale = $this->normalizeLocale($locale);
            if ($locale === '' || isset($existing[$locale])) {
                continue;
            }

            $slug = $this->generator->slugify($title);
            if ($slug === '') {
                continue;
            }

            $slug = $this->generator->uniquify(
                $slug,
                fn (string $candidate): bool => $this->isAvailable($resourceType, $locale, $candidate, $resourceId)
            );
            $this->upsert($resourceType, $resourceId, $locale, $slug);
            $existing[$locale] = $slug;
        }
    }

    /**
     * @return array<string, string> locale => slug
     */
    public function slugsForResource(string $resourceType, int $resourceId): array
    {
        $rows = $this->model
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->orderBy('locale', 'ASC')
            ->findAll();

        $slugs = [];
        foreach ($rows as $row) {
            $parsed = $this->parseRow($row);
            if ($parsed !== null) {
                $slugs[$parsed['locale']] = $parsed['slug'];
            }
        }

        return $slugs;
    }

    /**
     * @param list<int> $resourceIds
     * @return array<int, array<string, string>> resource id => (locale => slug)
     */
    public function slugsForResources(string $resourceType, array $resourceIds): array
    {
        $resourceIds = array_values(array_unique(array_filter($resourceIds, static fn (int $id): bool => $id > 0)));
        if ($resourceIds === []) {
            return [];
        }

        $rows = $this->model
            ->where('resource_type', $resourceType)
            ->whereIn('resource_id', $resourceIds)
            ->orderBy('resource_id', 'ASC')
            ->orderBy('locale', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $parsed = $this->parseRow($row);
            if ($parsed !== null) {
                $grouped[$parsed['resource_id']][$parsed['locale']] = $parsed['slug'];
            }
        }

        return $grouped;
    }

    /**
     * Find the resource a public slug points to. Locales requested via
     * Accept-Language are preferred; any locale matches as a fallback so a
     * shared URL keeps working for visitors browsing in another language.
     */
    public function resolveResourceId(string $resourceType, string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $rows = $this->model
            ->where('resource_type', $resourceType)
            ->where('slug', $slug)
            ->findAll();

        if ($rows === []) {
            return null;
        }

        $byLocale = [];
        foreach ($rows as $row) {
            $parsed = $this->parseRow($row);
            if ($parsed !== null) {
                $byLocale[$parsed['locale']] = $parsed['resource_id'];
            }
        }

        if ($byLocale === []) {
            return null;
        }

        foreach ($this->localeResolver->requestedLocales() as $locale) {
            if (isset($byLocale[$locale])) {
                return $byLocale[$locale];
            }
        }

        return (int) reset($byLocale);
    }

    /**
     * Pick the slug matching the request locale, falling back to the legacy
     * fallback locale and finally to any available slug.
     *
     * @param array<string, string> $slugsByLocale
     */
    public function resolveSlug(array $slugsByLocale): string
    {
        if ($slugsByLocale === []) {
            return '';
        }

        foreach ($this->localeResolver->requestedLocales() as $locale) {
            if (isset($slugsByLocale[$locale])) {
                return $slugsByLocale[$locale];
            }
        }

        $fallback = TranslationFieldCatalog::fallbackLocale();
        if (isset($slugsByLocale[$fallback])) {
            return $slugsByLocale[$fallback];
        }

        return (string) reset($slugsByLocale);
    }

    private function isAvailable(string $resourceType, string $locale, string $slug, int $excludeResourceId): bool
    {
        $builder = $this->model
            ->where('resource_type', $resourceType)
            ->where('locale', $locale)
            ->where('slug', $slug);

        if ($excludeResourceId > 0) {
            $builder->where('resource_id !=', $excludeResourceId);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * Validate a raw model row into the typed triple the callers need.
     *
     * @return array{resource_id: int, locale: string, slug: string}|null
     */
    private function parseRow(mixed $row): ?array
    {
        if (! is_array($row)) {
            return null;
        }

        $resourceId = $row['resource_id'] ?? null;
        $locale = $row['locale'] ?? null;
        $slug = $row['slug'] ?? null;

        if (! is_numeric($resourceId) || ! is_string($locale) || ! is_string($slug)) {
            return null;
        }

        return [
            'resource_id' => (int) $resourceId,
            'locale'      => $locale,
            'slug'        => $slug,
        ];
    }

    private function upsert(string $resourceType, int $resourceId, string $locale, string $slug): void
    {
        $existing = $this->model
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('locale', $locale)
            ->first();

        if (is_array($existing) && is_numeric($existing['id'] ?? null)) {
            $this->model->update((int) $existing['id'], ['slug' => $slug]);

            return;
        }

        $this->model->insert([
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'locale'        => $locale,
            'slug'          => $slug,
        ]);
    }

    private function normalizeLocale(string|int $locale): string
    {
        $locale = strtolower(str_replace('_', '-', trim((string) $locale)));

        return preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/', $locale) === 1 ? $locale : '';
    }
}
