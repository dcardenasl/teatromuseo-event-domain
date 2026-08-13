<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Events;

use CodeIgniter\Database\QueryInterface;
use CodeIgniter\Events\Events;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicReadQueryBudgetTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const WEB_API_KEY = 'test-web-api-key';

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        putenv('WEB_API_KEY=' . self::WEB_API_KEY);
        $_ENV['WEB_API_KEY'] = self::WEB_API_KEY;
        $_SERVER['WEB_API_KEY'] = self::WEB_API_KEY;

        $this->db->disableForeignKeyChecks();
        $this->db->table('event_public_slugs')->where('id >', 0)->delete();
        $this->db->table('event_translations')->where('id >', 0)->delete();
        $this->db->table('occurrences')->where('id >', 0)->delete();
        $this->db->table('events')->where('id >', 0)->delete();
        $this->db->enableForeignKeyChecks();
    }

    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
        parent::tearDown();
    }

    public function testListingKeepsAStableQueryBudgetAndUsesPublicReadIndexes(): void
    {
        $events = [];
        for ($index = 0; $index < 1000; $index++) {
            $published = $index < 160;
            $events[] = [
                'uuid' => sprintf('qa02-event-%04d', $index),
                'title' => sprintf('qa02-event-%04d', $index),
                'description' => 'QA-02 fixture',
                'event_type' => 'function',
                'status' => $published ? 'published' : 'draft',
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
        }
        $this->db->table('events')->insertBatch($events);

        $publishedEvents = $this->db->table('events')
            ->select('id, title')
            ->where('title >=', 'qa02-event-0000')
            ->where('title <', 'qa02-event-0160')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        $this->assertCount(160, $publishedEvents);

        $occurrences = [];
        $translations = [];
        $slugs = [];
        foreach ($publishedEvents as $event) {
            $id = (int) $event['id'];
            $occurrences[] = [
                'event_id' => $id,
                'venue_id' => null,
                'start_time' => '2030-01-01 10:00:00',
                'end_time' => '2030-01-01 11:00:00',
                'status' => 'scheduled',
                'capacity' => 100,
                'available_spots' => 100,
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
            $translations[] = [
                'translatable_type' => 'event',
                'translatable_id' => $id,
                'locale' => 'es',
                'field' => 'title',
                'value' => (string) $event['title'],
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
            $slugs[] = [
                'resource_type' => 'event',
                'resource_id' => $id,
                'locale' => 'es',
                'slug' => (string) $event['title'],
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
        }
        $this->db->table('occurrences')->insertBatch($occurrences);
        $this->db->table('event_translations')->insertBatch($translations);
        $this->db->table('event_public_slugs')->insertBatch($slugs);

        $measurement = $this->measureGet('/api/v1/public-read/es/events?fields=id,title,next_occurrence_at&per_page=24');
        $measurement['response']->assertStatus(200);

        $body = json_decode((string) $measurement['response']->getJSON(), true);
        $this->assertSame(160, $body['meta']['total'] ?? null);
        $this->assertCount(24, $body['data'] ?? []);
        $this->assertLessThanOrEqual(6, $measurement['query_count'], $this->querySummary($measurement['queries']));
        $this->assertLessThanOrEqual(500.0, $this->totalDuration($measurement['queries']), $this->querySummary($measurement['queries']));

        $listingSql = $this->findQuery($measurement['queries'], 'FROM `events` `e`');
        $this->assertNotNull($listingSql, $this->querySummary($measurement['queries']));

        $plan = $this->db->query('EXPLAIN ' . $listingSql)->getResultArray();
        $projectionPlan = $this->findPlanRowStartingWith($plan, '<derived');
        $this->assertNotNull($projectionPlan, json_encode($plan, JSON_UNESCAPED_SLASHES));

        // The projection is intentionally materialized once per request. The
        // important regression guard is that its occurrence scan uses the
        // public-read index, rather than repeating correlated aggregates.
        $occurrencePlan = $this->findPlanRow($plan, 'o');
        $this->assertNotNull($occurrencePlan, json_encode($plan, JSON_UNESCAPED_SLASHES));
        $this->assertSame('idx_occurrences_public_read', $occurrencePlan['key'] ?? null, json_encode($occurrencePlan));
        $this->assertNotSame('ALL', $occurrencePlan['type'] ?? null, json_encode($occurrencePlan));

        $this->assertTrue(
            $this->planHasKey($plan, 'o', 'idx_occurrences_public_read'),
            json_encode($plan, JSON_UNESCAPED_SLASHES),
        );

        // Regression for docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md
        // §2.D/§1.6: `events` itself only had single-column status/event_type
        // indexes, and this table's own access plan was never verified here —
        // a real coverage gap. Measured with idx_events_public_listing
        // (status, deleted_at, event_type) present: for this reader's
        // "agenda"-sort query, MySQL drives the join from
        // occurrence_projection (the smaller, already-has-occurrences side)
        // and probes `events` via PRIMARY eq_ref — genuinely the optimal
        // plan here, better than any secondary index. Verified separately
        // (scratch EXPLAIN, not committed as a test) that a standalone
        // `events` filter — `WHERE status='published' AND deleted_at IS NULL
        // AND event_type=?`, the shape this index exists for — does select
        // idx_events_public_listing (`type=ref`, `Using index`) once the
        // occurrence-projection join isn't in the way. The regression this
        // assertion guards against is a full table scan on `events`, not one
        // specific key — see §1.6's own note that adding an index "by
        // symmetry" without measuring is exactly what this audit argues
        // against; `possible_keys` confirms the optimizer did consider it.
        $eventPlan = $this->findPlanRow($plan, 'e');
        $this->assertNotNull($eventPlan, json_encode($plan, JSON_UNESCAPED_SLASHES));
        $this->assertNotSame('ALL', $eventPlan['type'] ?? null, json_encode($eventPlan));
        $this->assertStringContainsString(
            'idx_events_public_listing',
            (string) ($eventPlan['possible_keys'] ?? ''),
            json_encode($eventPlan),
        );
    }

    public function testShowMinimalAndCompleteFieldsetsStayWithinSeparateBudgets(): void
    {
        $eventId = $this->createBudgetEvent('QA show budget');

        $minimal = $this->measureGet('/api/v1/public-read/es/events/' . $eventId . '?fields=id');
        $minimal['response']->assertStatus(200);
        $this->assertLessThanOrEqual(2, $minimal['query_count'], $this->querySummary($minimal['queries']));
        $this->assertNoStandaloneHydrationQueries($minimal['queries']);

        $completeFields = implode(',', [
            'id', 'uuid', 'title', 'event_type', 'description', 'slug', 'slugs',
            'translations', 'occurrences', 'status', 'created_at', 'updated_at',
        ]);
        $complete = $this->measureGet('/api/v1/public-read/es/events/' . $eventId . '?fields=' . $completeFields);
        $complete['response']->assertStatus(200);
        $this->assertLessThanOrEqual(5, $complete['query_count'], $this->querySummary($complete['queries']));
    }

    public function testFiltersAndSearchKeepAStableListingBudget(): void
    {
        for ($index = 0; $index < 12; $index++) {
            $this->createBudgetEvent($index === 3 ? 'QA searchable function' : 'QA other function');
        }

        $measurement = $this->measureGet(
            '/api/v1/public-read/es/events?fields=id,title&event_type=function&search=searchable'
            . '&from=2026-08-10%2010:00:00&to=2026-08-20%2010:00:00&per_page=10',
        );
        $measurement['response']->assertStatus(200);
        $this->assertLessThanOrEqual(5, $measurement['query_count'], $this->querySummary($measurement['queries']));
        $this->assertLessThanOrEqual(500.0, $this->totalDuration($measurement['queries']), $this->querySummary($measurement['queries']));

        $listingSql = $this->findQuery($measurement['queries'], 'FROM `events` `e`');
        $this->assertNotNull($listingSql, $this->querySummary($measurement['queries']));
        $this->assertStringContainsString('ofilter', $listingSql);
        $this->assertStringContainsString('event_translations', $listingSql);
    }

    public function testLegacyPublicListingRemainsWithinItsCompatibilityBudget(): void
    {
        for ($index = 0; $index < 24; $index++) {
            $this->createBudgetEvent('QA legacy ' . $index);
        }

        $measurement = $this->measureGet('/api/v1/public/events?per_page=24');
        $measurement['response']->assertStatus(200);
        $this->assertLessThanOrEqual(12, $measurement['query_count'], $this->querySummary($measurement['queries']));
        $this->assertLessThanOrEqual(500.0, $this->totalDuration($measurement['queries']), $this->querySummary($measurement['queries']));
    }

    /**
     * @return array{response: \CodeIgniter\Test\TestResponse, queries: list<array{sql:string,duration_ms:float}>, query_count:int}
     */
    private function measureGet(string $path): array
    {
        $queries = [];
        $listener = static function (mixed $query) use (&$queries): void {
            if (! $query instanceof QueryInterface) {
                return;
            }

            $sql = trim((string) $query);
            if (str_starts_with(strtoupper($sql), 'SELECT')) {
                $queries[] = [
                    'sql' => $sql,
                    'duration_ms' => (float) $query->getDuration(6) * 1000,
                ];
            }
        };

        Events::on('DBQuery', $listener, Events::PRIORITY_LOW);
        $startedAt = microtime(true);
        try {
            $response = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get($path);
        } finally {
            Events::removeListener('DBQuery', $listener);
        }

        $elapsedMs = (microtime(true) - $startedAt) * 1000;
        $this->assertLessThan(1000, $elapsedMs, $this->querySummary($queries));

        return ['response' => $response, 'queries' => $queries, 'query_count' => count($queries)];
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function findQuery(array $queries, string $fragment): ?string
    {
        foreach ($queries as $query) {
            if (str_contains($query['sql'], $fragment) && str_contains($query['sql'], 'ORDER BY')) {
                return $query['sql'];
            }
        }

        return null;
    }

    /** @param list<array<string,mixed>> $plan */
    private function findPlanRow(array $plan, string $table): ?array
    {
        foreach ($plan as $row) {
            if (($row['table'] ?? null) === $table) {
                return $row;
            }
        }

        return null;
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function assertNoStandaloneHydrationQueries(array $queries): void
    {
        foreach ($queries as $query) {
            $sql = $query['sql'];
            $this->assertStringNotContainsString('FROM `event_translations`', $sql);
            $this->assertStringNotContainsString('FROM `event_public_slugs`', $sql);
            $this->assertStringNotContainsString('JOIN `venues`', $sql);
        }
    }

    private function createBudgetEvent(string $title): int
    {
        $this->db->table('events')->insert([
            'uuid' => 'qa-budget-' . bin2hex(random_bytes(8)),
            'title' => $title,
            'description' => 'QA budget description',
            'event_type' => 'function',
            'status' => 'published',
            'created_at' => '2026-08-11 09:00:00',
            'updated_at' => '2026-08-11 09:00:00',
        ]);
        $eventId = (int) $this->db->insertID();
        $this->db->table('occurrences')->insert([
            'event_id' => $eventId,
            'venue_id' => null,
            'start_time' => '2026-08-15 10:00:00',
            'end_time' => '2026-08-15 11:00:00',
            'status' => 'scheduled',
            'capacity' => 100,
            'available_spots' => 100,
            'created_at' => '2026-08-11 09:00:00',
            'updated_at' => '2026-08-11 09:00:00',
        ]);
        $this->db->table('event_translations')->insertBatch([
            [
                'translatable_type' => 'event',
                'translatable_id' => $eventId,
                'locale' => 'es',
                'field' => 'title',
                'value' => $title,
                'created_at' => '2026-08-11 09:00:00',
                'updated_at' => '2026-08-11 09:00:00',
            ],
            [
                'translatable_type' => 'event',
                'translatable_id' => $eventId,
                'locale' => 'es',
                'field' => 'description',
                'value' => 'QA budget description',
                'created_at' => '2026-08-11 09:00:00',
                'updated_at' => '2026-08-11 09:00:00',
            ],
        ]);
        $this->db->table('event_public_slugs')->insert([
            'resource_type' => 'event',
            'resource_id' => $eventId,
            'locale' => 'es',
            'slug' => 'qa-budget-' . $eventId,
            'created_at' => '2026-08-11 09:00:00',
            'updated_at' => '2026-08-11 09:00:00',
        ]);

        return $eventId;
    }

    /** @param list<array<string,mixed>> $plan */
    private function findPlanRowStartingWith(array $plan, string $tablePrefix): ?array
    {
        foreach ($plan as $row) {
            if (is_string($row['table'] ?? null) && str_starts_with($row['table'], $tablePrefix)) {
                return $row;
            }
        }

        return null;
    }

    /** @param list<array<string,mixed>> $plan */
    private function planHasKey(array $plan, string $table, string $key): bool
    {
        foreach ($plan as $row) {
            if (($row['table'] ?? null) === $table && ($row['key'] ?? null) === $key) {
                return true;
            }
        }

        return false;
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function querySummary(array $queries): string
    {
        return json_encode($queries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'no queries';
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function totalDuration(array $queries): float
    {
        $total = 0.0;
        foreach ($queries as $query) {
            $total += $query['duration_ms'];
        }

        return $total;
    }
}
