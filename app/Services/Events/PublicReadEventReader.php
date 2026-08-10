<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\DTO\Request\Events\PublicReadEventRequestDTO;
use App\Interfaces\Events\PublicReadEventReaderInterface;
use App\Libraries\Hub\HubClient;
use App\Modules\PublicRead\Support\PublicReadEnvelope;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Support\ApiResult;

/**
 * SQL-first public event reader. Hydration is limited to the selected page;
 * the legacy EventService listing remains available for the admin surface.
 */
final class PublicReadEventReader implements PublicReadEventReaderInterface
{
    /** @var list<string> */
    private const PUBLIC_COLUMNS = [
        'id', 'uuid', 'title', 'event_type', 'description', 'cover_file_id',
        'gallery_file_ids', 'status', 'created_at', 'updated_at',
    ];

    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(
        private readonly BaseConnection $db,
        private readonly HubClient $hubClient,
        private readonly string $timezone = 'UTC',
        private readonly string $fallbackLocale = 'es',
    ) {
    }

    /** @param list<string> $fields */
    public function index(PublicReadEventRequestDTO $request, array $fields): ApiResult
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone($this->timezone)))->format('Y-m-d H:i:s');
        $builder = $this->baseBuilder($request->locale);
        $this->applyFilters($builder, $request);

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->countAllResults();

        $nextOccurrence = $this->nextOccurrenceExpression($now);
        if ($request->sort === 'latest') {
            $builder->orderBy('e.created_at', 'DESC')->orderBy('e.id', 'DESC');
        } elseif ($request->sort === 'id') {
            $builder->orderBy('e.id', 'ASC');
        } elseif ($request->sort === 'title') {
            $builder->orderBy('e.title', 'ASC')->orderBy('e.id', 'ASC');
        } else {
            // Future events first, chronological; past events follow in reverse.
            $lastOccurrence = $this->lastOccurrenceExpression($now);
            $builder->orderBy("CASE WHEN {$nextOccurrence} IS NULL THEN 1 ELSE 0 END", 'ASC', false)
                ->orderBy($nextOccurrence, 'ASC', false)
                ->orderBy($lastOccurrence, 'DESC', false)
                ->orderBy('e.id', 'ASC');
        }

        $builder->select(implode(', ', array_map(static fn (string $column): string => 'e.' . $column, $this->columnsFor($fields))) . ", {$nextOccurrence} AS next_occurrence_at", false);
        $builder->limit($request->perPage, ($request->page - 1) * $request->perPage);
        $query = $builder->get();
        $rows = $query !== false ? array_values($query->getResultArray()) : [];
        $data = $this->hydrate($rows, $request->locale, false, $fields);

        return PublicReadEnvelope::success(
            locale: $request->locale,
            data: $data,
            sourceRevision: $this->revision($rows),
            page: $request->page,
            perPage: $request->perPage,
            total: $total,
            meta: ['fields' => $fields, 'query' => $request->toArray()],
        );
    }

    /** @param list<string> $fields */
    public function show(string $locale, string $idOrSlug, array $fields): ApiResult
    {
        $builder = $this->baseBuilder($locale);
        $builder->select(implode(', ', array_map(static fn (string $column): string => 'e.' . $column, $this->columnsFor($fields))));
        if (ctype_digit(trim($idOrSlug))) {
            $builder->where('e.id', (int) $idOrSlug);
        } else {
            $builder->groupStart()
                ->where('e.uuid', trim($idOrSlug))
                ->orWhere(
                    "EXISTS (SELECT 1 FROM event_public_slugs ps WHERE ps.resource_type = 'event' AND ps.resource_id = e.id AND ps.locale IN (" . $this->db->escape($locale) . ', ' . $this->db->escape($this->fallbackLocale) . ") AND ps.slug = " . $this->db->escape(trim($idOrSlug)) . ')',
                    null,
                    false,
                )
                ->groupEnd();
        }

        $query = $builder->get();
        $row = $query !== false ? $query->getRowArray() : null;
        if ($row === null) {
            return $this->notFound($locale);
        }

        $data = $this->hydrate([$row], $locale, true, $fields)[0] ?? [];

        return PublicReadEnvelope::success(
            locale: $locale,
            data: $data,
            sourceRevision: $this->revision([$row]),
            meta: ['fields' => $fields, 'query' => ['id_or_slug' => $idOrSlug]],
        );
    }

    private function baseBuilder(string $locale): \CodeIgniter\Database\BaseBuilder
    {
        $builder = $this->db->table('events e');
        $builder->where('e.status', 'published')->where('e.deleted_at', null);
        $builder->where(
            "EXISTS (SELECT 1 FROM occurrences ov WHERE ov.event_id = e.id AND ov.deleted_at IS NULL)",
            null,
            false,
        );

        return $builder;
    }

    private function applyFilters(\CodeIgniter\Database\BaseBuilder $builder, PublicReadEventRequestDTO $request): void
    {
        if ($request->eventType !== '') {
            $builder->where('e.event_type', $request->eventType);
        }
        if ($request->search !== '') {
            $search = (string) $this->db->escape('%' . $request->search . '%');
            $builder->groupStart()
                ->like('e.title', $request->search)
                ->orLike('e.description', $request->search)
                ->orWhere("EXISTS (SELECT 1 FROM event_translations ets WHERE ets.translatable_type = 'event' AND ets.translatable_id = e.id AND ets.locale IN (" . $this->db->escape($request->locale) . ', ' . $this->db->escape($this->fallbackLocale) . ") AND ets.field IN ('title', 'description') AND ets.value LIKE {$search})", null, false)
                ->groupEnd();
        }
        if ($request->from !== '') {
            $builder->where(
                "EXISTS (SELECT 1 FROM occurrences ofrom WHERE ofrom.event_id = e.id AND ofrom.deleted_at IS NULL AND ofrom.start_time >= " . $this->db->escape($request->from) . ')',
                null,
                false,
            );
        }
        if ($request->to !== '') {
            $builder->where(
                "EXISTS (SELECT 1 FROM occurrences oto WHERE oto.event_id = e.id AND oto.deleted_at IS NULL AND oto.start_time <= " . $this->db->escape($request->to) . ')',
                null,
                false,
            );
        }
    }

    private function nextOccurrenceExpression(string $now): string
    {
        return "(SELECT MIN(oc.start_time) FROM occurrences oc WHERE oc.event_id = e.id AND oc.deleted_at IS NULL AND oc.start_time >= " . $this->db->escape($now) . ')';
    }

    private function lastOccurrenceExpression(string $now): string
    {
        return "(SELECT MAX(oc.start_time) FROM occurrences oc WHERE oc.event_id = e.id AND oc.deleted_at IS NULL AND oc.start_time < " . $this->db->escape($now) . ')';
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function hydrate(array $rows, string $locale, bool $detail, array $fields): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['id'], $rows)));
        $translationQuery = $this->db->table('event_translations')
            ->select('translatable_id, locale, field, value')
            ->where('translatable_type', 'event')
            ->whereIn('translatable_id', $ids)
            ->whereIn('locale', array_values(array_unique([$locale, $this->fallbackLocale])))
            ->get();
        $translationRows = $translationQuery !== false ? $translationQuery->getResultArray() : [];
        $translations = [];
        foreach ($translationRows as $translation) {
            $translations[(int) $translation['translatable_id']][(string) $translation['locale']][(string) $translation['field']] = (string) $translation['value'];
        }

        $slugQuery = $this->db->table('event_public_slugs')
            ->select('resource_id, locale, slug')
            ->where('resource_type', 'event')
            ->whereIn('resource_id', $ids)
            ->whereIn('locale', array_values(array_unique([$locale, $this->fallbackLocale])))
            ->get();
        $slugRows = $slugQuery !== false ? $slugQuery->getResultArray() : [];
        $slugs = [];
        foreach ($slugRows as $slug) {
            $slugs[(int) $slug['resource_id']][(string) $slug['locale']] = (string) $slug['slug'];
        }

        $occurrencesByEvent = [];
        if ($detail) {
            $occurrenceQuery = $this->db->table('occurrences o')
                ->select('o.event_id, o.id, o.venue_id, o.start_time, o.end_time, o.status, o.capacity, o.available_spots, v.name AS venue_name')
                ->join('venues v', 'v.id = o.venue_id', 'left')
                ->whereIn('o.event_id', $ids)
                ->where('o.deleted_at', null)
                ->orderBy('o.start_time', 'ASC')
                ->get();
            $occurrences = $occurrenceQuery !== false ? $occurrenceQuery->getResultArray() : [];
            foreach ($occurrences as $occurrence) {
                $occurrencesByEvent[(int) $occurrence['event_id']][] = $occurrence;
            }
        }

        $resolveCover = $fields === [] || in_array('cover_image', $fields, true);
        $resolveGallery = $fields === [] || in_array('gallery_images', $fields, true);
        $fileIds = [];
        foreach ($rows as $row) {
            $fileIds = array_merge($fileIds, $this->fileIds($row, $resolveCover, $resolveGallery));
        }
        $media = $this->resolveMedia($fileIds);

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $localized = [
                'title' => $translations[$id][$locale]['title'] ?? $translations[$id][$this->fallbackLocale]['title'] ?? $row['title'],
                'description' => $translations[$id][$locale]['description'] ?? $translations[$id][$this->fallbackLocale]['description'] ?? $row['description'],
            ];
            $payload = [
                'id' => $id,
                'uuid' => (string) ($row['uuid'] ?? ''),
                'title' => $localized['title'],
                'event_type' => (string) ($row['event_type'] ?? ''),
                'description' => $localized['description'],
                'cover_file_id' => isset($row['cover_file_id']) ? (int) $row['cover_file_id'] : null,
                'gallery_file_ids' => $row['gallery_file_ids'] ?? null,
                'cover_image' => $this->mediaItem($media, (int) ($row['cover_file_id'] ?? 0)),
                'gallery_images' => $this->galleryMedia($media, $row['gallery_file_ids'] ?? null),
                'translations' => $this->translationPayload($translations[$id] ?? []),
                'localized' => $localized,
                'slug' => $slugs[$id][$locale] ?? $slugs[$id][$this->fallbackLocale] ?? '',
                'slugs' => $slugs[$id] ?? [],
                'occurrences' => $detail ? ($occurrencesByEvent[$id] ?? []) : [],
                'next_occurrence_at' => $row['next_occurrence_at'] ?? null,
                'status' => (string) ($row['status'] ?? ''),
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];
            $result[] = $this->filterFields($payload, $fields);
        }

        return $result;
    }

    /**
     * @param array<string, array<string, string>> $rows
     * @return list<array<string, string>>
     */
    private function translationPayload(array $rows): array
    {
        $payload = [];
        foreach ($rows as $locale => $fields) {
            $payload[] = ['locale' => $locale, ...$fields];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    private function filterFields(array $payload, array $fields): array
    {
        return $fields === [] ? $payload : array_intersect_key($payload, array_flip($fields));
    }

    /**
     * @param array<string, mixed> $row
     * @return list<int>
     */
    private function fileIds(array $row, bool $resolveCover, bool $resolveGallery): array
    {
        $ids = [];
        if ($resolveCover && (int) ($row['cover_file_id'] ?? 0) > 0) {
            $ids[] = (int) $row['cover_file_id'];
        }
        if ($resolveGallery) {
            foreach (explode(',', (string) ($row['gallery_file_ids'] ?? '')) as $rawId) {
                if ((int) trim($rawId) > 0) {
                    $ids[] = (int) trim($rawId);
                }
            }
        }

        return array_map(static fn (mixed $id): int => (int) $id, array_values(array_unique($ids)));
    }

    /**
     * @param array<int, mixed> $ids
     * @return array<int, array<string, mixed>>
     */
    private function resolveMedia(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $normalized = array_values(array_map(static fn (mixed $id): int => (int) $id, $ids));

        return $this->hubClient->resolvePublicFileMeta($normalized);
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @return array<string, mixed>|null
     */
    private function mediaItem(array $media, int $id): ?array
    {
        if ($id <= 0 || !isset($media[$id])) {
            return null;
        }
        $meta = $media[$id];
        return [
            'source_kind' => 'hub_file',
            'file_id' => $id,
            'url' => $meta['url'] ?? null,
            'variants' => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @return list<array<string, mixed>>
     */
    private function galleryMedia(array $media, mixed $rawIds): array
    {
        $result = [];
        foreach (explode(',', (string) $rawIds) as $rawId) {
            $item = $this->mediaItem($media, (int) trim($rawId));
            if ($item !== null) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $rows */
    private function revision(array $rows): string
    {
        $updated = '';
        $maxId = 0;
        foreach ($rows as $row) {
            $updated = max($updated, (string) ($row['updated_at'] ?? ''));
            $maxId = max($maxId, (int) ($row['id'] ?? 0));
        }

        return 'events:' . ($updated !== '' ? $updated : 'empty') . ':' . $maxId;
    }

    private function notFound(string $locale): ApiResult
    {
        return new ApiResult([
            'version' => 1,
            'ok' => false,
            'data' => null,
            'meta' => ['locale' => $locale, 'source_revision' => 'events:empty'],
            'source' => ['domain' => 'events', 'state' => 'unavailable', 'stale' => false],
            'messages' => ['Event not found.'],
        ], 404);
    }

    /**
     * @param list<string> $fields
     * @return list<string>
     */
    private function columnsFor(array $fields): array
    {
        if ($fields === []) {
            return self::PUBLIC_COLUMNS;
        }

        $required = ['id', 'uuid', 'title', 'description', 'event_type', 'status', 'updated_at'];
        $fieldColumns = array_intersect(self::PUBLIC_COLUMNS, $fields);
        if (in_array('cover_image', $fields, true)) {
            $fieldColumns[] = 'cover_file_id';
        }
        if (in_array('gallery_images', $fields, true)) {
            $fieldColumns[] = 'gallery_file_ids';
        }
        return array_values(array_unique(array_merge($required, $fieldColumns)));
    }
}
