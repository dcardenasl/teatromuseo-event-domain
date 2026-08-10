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
        $eventDriverPlan = $this->findPlanRow($plan, 'ov');
        $this->assertNotNull($eventDriverPlan, json_encode($plan, JSON_UNESCAPED_SLASHES));
        $this->assertSame('idx_occurrences_public_read', $eventDriverPlan['key'] ?? null, json_encode($eventDriverPlan));
        $this->assertNotSame('ALL', $eventDriverPlan['type'] ?? null, json_encode($eventDriverPlan));

        $this->assertTrue(
            $this->planHasKey($plan, 'oc', 'idx_occurrences_public_read'),
            json_encode($plan, JSON_UNESCAPED_SLASHES),
        );
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
