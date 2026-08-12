<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Events;

use App\DTO\Request\Events\EventCreateRequestDTO;
use App\DTO\Request\Events\OccurrenceCreateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class PublicEventControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const WEB_API_KEY = 'test-web-api-key';

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        putenv('WEB_API_KEY=' . self::WEB_API_KEY);
        $_ENV['WEB_API_KEY'] = self::WEB_API_KEY;
        $_SERVER['WEB_API_KEY'] = self::WEB_API_KEY;

        $this->db->table('event_public_slugs')->truncate();
        $this->db->table('event_translations')->truncate();
        $this->db->table('events')->where('id >', 0)->delete();
    }

    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
        parent::tearDown();
    }

    public function testIndexRejectsMissingAppKey(): void
    {
        $result = $this->get('/api/v1/public/events');

        $result->assertStatus(401);
    }

    public function testIndexReturnsOnlyPublishedEvents(): void
    {
        $this->createEvent('Función Viva', 'published');
        $this->createEvent('Ensayo Cerrado', 'draft');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get('/api/v1/public/events');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $titles = array_column($body['data'] ?? [], 'title');
        $this->assertContains('Función Viva', $titles);
        $this->assertNotContains('Ensayo Cerrado', $titles);
    }

    public function testPublicCarteleraExcludesPublishedEventsWithoutOccurrences(): void
    {
        $event = Services::eventService(false)->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => 'Función sin horario',
            'event_type' => 'function',
            'description' => 'No debe publicarse sin una función programada.',
            'status' => 'published',
        ]))->toArray();

        $listing = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get('/api/v1/public/events');
        $listing->assertStatus(200);
        $body = json_decode((string) $listing->getJSON(), true);
        $titles = array_column($body['data'] ?? [], 'title');

        $this->assertNotContains('Función sin horario', $titles);
        $this->assertSame(0, (int) ($body['meta']['total'] ?? 0));

        $detail = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/events/' . $event['id']);
        $detail->assertStatus(404);
    }

    public function testTypesReturnsActiveEventTypeCatalogue(): void
    {
        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get('/api/v1/public/events/types');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $types = array_column($body['data'] ?? [], 'slug');

        $this->assertSame(['function', 'festival', 'course', 'workshop', 'other'], $types);
    }

    public function testIndexOrdersUpcomingFirstThenMostRecentPast(): void
    {
        // A real "cartelera" reads: what's playing next, then — scrolling down — what just
        // played, oldest last. A single ascending/descending `sort` can't express that split,
        // so this exercises the dedicated indexPublicCartelera() path end-to-end.
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createEvent('Hace un año', 'published', $now->modify('-1 year')->format('Y-m-d H:i:s'));
        $this->createEvent('Mañana', 'published', $now->modify('+1 day')->format('Y-m-d H:i:s'));
        $this->createEvent('Ayer', 'published', $now->modify('-1 day')->format('Y-m-d H:i:s'));
        $this->createEvent('En un mes', 'published', $now->modify('+1 month')->format('Y-m-d H:i:s'));
        $this->createEvent('Hace una semana', 'published', $now->modify('-1 week')->format('Y-m-d H:i:s'));

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get('/api/v1/public/events');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $titles = array_column($body['data'] ?? [], 'title');
        $this->assertSame(['Mañana', 'En un mes', 'Ayer', 'Hace una semana', 'Hace un año'], $titles);
    }

    public function testPublicReadListingUsesVersionedEnvelopeAndSqlOccurrenceProjection(): void
    {
        $this->createEvent('PublicRead Agenda', 'published');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/events?fields=id,title,next_occurrence_at');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertPublicReadEnvelope($body, 'events');
        $this->assertSame('PublicRead Agenda', $body['data'][0]['title'] ?? null);
        $this->assertNotEmpty($body['data'][0]['next_occurrence_at'] ?? null);
    }

    public function testPublicReadListingFallsBackToTheDefaultLocale(): void
    {
        $this->createEvent('PublicRead fallback', 'published');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/events?fields=id,title');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertPublicReadEnvelope($body, 'events');
        $this->assertSame('PublicRead fallback', $body['data'][0]['title'] ?? null);
    }

    public function testPublicReadListingRejectsMissingAppKey(): void
    {
        $result = $this->get('/api/v1/public-read/es/events');

        $result->assertStatus(401);
    }

    public function testPublicReadAgendaOrdersFutureAscendingThenPastDescending(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createEvent('Read Hace un año', 'published', $now->modify('-1 year')->format('Y-m-d H:i:s'));
        $this->createEvent('Read Mañana', 'published', $now->modify('+1 day')->format('Y-m-d H:i:s'));
        $this->createEvent('Read Ayer', 'published', $now->modify('-1 day')->format('Y-m-d H:i:s'));
        $this->createEvent('Read En un mes', 'published', $now->modify('+1 month')->format('Y-m-d H:i:s'));

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/events?fields=title');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertSame(
            ['Read Mañana', 'Read En un mes', 'Read Ayer', 'Read Hace un año'],
            array_column($body['data'] ?? [], 'title'),
        );
    }

    public function testPublicReadRangeRequiresOneOccurrenceToSatisfyBothBounds(): void
    {
        $split = $this->createEvent('Read Split Range', 'published', '2026-08-01 10:00:00');
        $this->insertOccurrence((int) $split['id'], '2026-08-30 10:00:00');
        $this->createEvent('Read Matching Range', 'published', '2026-08-15 10:00:00');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/es/events?fields=id,title&from=2026-08-10%2010:00:00&to=2026-08-20%2010:00:00',
        );

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertSame(['Read Matching Range'], array_column($body['data'] ?? [], 'title'));
    }

    public function testPublicReadRejectsAnInvertedOccurrenceRange(): void
    {
        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/es/events?from=2026-08-20%2010:00:00&to=2026-08-10%2010:00:00',
        );

        $result->assertStatus(422);
    }

    public function testPublicReadResolvesParentLocaleAndPrefersItForSlugCollisions(): void
    {
        $parentLocaleEvent = $this->createEvent('Parent Locale Event', 'published');
        $preferredEvent = $this->createEvent('Preferred Locale Event', 'published');
        $fallbackEvent = $this->createEvent('Fallback Locale Event', 'published');

        $this->db->table('event_translations')->insert([
            'translatable_type' => 'event',
            'translatable_id' => $parentLocaleEvent['id'],
            'locale' => 'en',
            'field' => 'title',
            'value' => 'Parent English Event',
            'created_at' => '2026-08-11 10:00:00',
            'updated_at' => '2026-08-11 10:00:00',
        ]);
        $this->db->table('event_public_slugs')->insert([
            'resource_type' => 'event',
            'resource_id' => $parentLocaleEvent['id'],
            'locale' => 'en',
            'slug' => 'parent-english-event',
            'created_at' => '2026-08-11 10:00:00',
            'updated_at' => '2026-08-11 10:00:00',
        ]);
        $this->db->table('event_public_slugs')->insert([
            'resource_type' => 'event',
            'resource_id' => $preferredEvent['id'],
            'locale' => 'en',
            'slug' => 'shared-event',
            'created_at' => '2026-08-11 10:00:00',
            'updated_at' => '2026-08-11 10:00:00',
        ]);
        $this->db->table('event_public_slugs')
            ->where('resource_type', 'event')
            ->where('resource_id', $fallbackEvent['id'])
            ->where('locale', 'es')
            ->update([
                'slug' => 'shared-event',
                'updated_at' => '2026-08-11 10:00:00',
            ]);

        $parentResult = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/en-US/events/parent-english-event?fields=id,title,slug',
        );
        $parentResult->assertStatus(200);
        $parentBody = json_decode((string) $parentResult->getJSON(), true);
        $this->assertSame((int) $parentLocaleEvent['id'], $parentBody['data']['id'] ?? null);
        $this->assertSame('Parent English Event', $parentBody['data']['title'] ?? null);

        $collisionResult = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/en/events/shared-event?fields=id',
        );
        $collisionResult->assertStatus(200);
        $collisionBody = json_decode((string) $collisionResult->getJSON(), true);
        $this->assertSame((int) $preferredEvent['id'], $collisionBody['data']['id'] ?? null);
    }

    public function testPublicReadSparseDetailDoesNotHydrateUnrequestedChildren(): void
    {
        $event = $this->createEvent('Sparse Detail', 'published');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/es/events/' . $event['uuid'] . '?fields=id',
        );

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertSame(['id'], array_keys($body['data'] ?? []));
    }

    public function testPublicReadSourceRevisionChangesWhenOccurrenceOrTranslationChanges(): void
    {
        $event = $this->createEvent('Revision Event', 'published');

        $first = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/es/events/' . $event['uuid'] . '?fields=id,title',
        );
        $firstBody = json_decode((string) $first->getJSON(), true);
        $firstRevision = $firstBody['meta']['source_revision'] ?? null;

        $this->db->table('occurrences')->where('event_id', $event['id'])->update([
            'updated_at' => '2040-01-01 10:00:00',
        ]);
        $second = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/es/events/' . $event['uuid'] . '?fields=id,title',
        );
        $secondBody = json_decode((string) $second->getJSON(), true);
        $secondRevision = $secondBody['meta']['source_revision'] ?? null;
        $this->assertNotSame($firstRevision, $secondRevision);

        $this->db->table('event_translations')
            ->where('translatable_type', 'event')
            ->where('translatable_id', $event['id'])
            ->where('locale', 'es')
            ->where('field', 'title')
            ->update([
                'value' => 'Revision Event Updated',
                'updated_at' => '2041-01-01 10:00:00',
            ]);
        $third = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get(
            '/api/v1/public-read/es/events/' . $event['uuid'] . '?fields=id,title',
        );
        $thirdBody = json_decode((string) $third->getJSON(), true);
        $this->assertNotSame($secondRevision, $thirdBody['meta']['source_revision'] ?? null);
    }

    public function testPublicReadDetailUsesUuidAndReturnsCanonicalEnvelope(): void
    {
        $event = $this->createEvent('PublicRead Detail', 'published');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/events/' . $event['uuid']);

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertTrue($body['ok'] ?? false);
        $this->assertSame((int) $event['id'], $body['data']['id'] ?? null);
        $this->assertNotEmpty($body['data']['occurrences'] ?? []);
    }

    public function testShowResolvesTheGeneratedSlug(): void
    {
        $created = $this->createEvent('Función Viva', 'published');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get('/api/v1/public/events/funcion-viva');

        $result->assertStatus(200);
        // Detail responses are the bare resource (same contract as the
        // catalog-domain public show); the web client unwraps data ?? body.
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame((int) $created['id'], $body['id']);
        $this->assertSame('funcion-viva', $body['slugs']['es'] ?? null);
    }

    public function testShowResolvesTheUuid(): void
    {
        $created = $this->createEvent('Función Viva', 'published');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/events/' . $created['uuid']);

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame((int) $created['id'], $body['id']);
    }

    public function testShowReturns404ForDraftEvents(): void
    {
        $this->createEvent('Ensayo Cerrado', 'draft');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/events/ensayo-cerrado');

        $result->assertStatus(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function createEvent(string $title, string $status, ?string $startTime = null): array
    {
        $event = Services::eventService(false)->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => $title,
            'event_type' => 'function',
            'description' => 'Descripción de ' . $title,
            'status' => $status,
        ]))->toArray();

        $start = $startTime ?? (new \DateTimeImmutable('now', new \DateTimeZone('America/Santiago')))
            ->modify('+1 hour')
            ->format('Y-m-d H:i:s');
        $end = (new \DateTimeImmutable($start, new \DateTimeZone('America/Santiago')))
            ->modify('+2 hours')
            ->format('Y-m-d H:i:s');

        Services::occurrenceService(false)->store(Services::requestDtoFactory()->make(OccurrenceCreateRequestDTO::class, [
            'event_id' => $event['id'],
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'scheduled',
            'capacity' => 0,
            'available_spots' => 0,
        ]));

        return $event;
    }

    private function insertOccurrence(int $eventId, string $startTime): void
    {
        $this->db->table('occurrences')->insert([
            'event_id' => $eventId,
            'venue_id' => null,
            'start_time' => $startTime,
            'end_time' => (new \DateTimeImmutable($startTime, new \DateTimeZone('UTC')))
                ->modify('+1 hour')
                ->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'capacity' => 0,
            'available_spots' => 0,
            'created_at' => '2026-08-11 10:00:00',
            'updated_at' => '2026-08-11 10:00:00',
        ]);
    }

    /** @param array<string, mixed> $body */
    private function assertPublicReadEnvelope(array $body, string $domain): void
    {
        $this->assertTrue($body['ok'] ?? false);
        $this->assertSame(1, $body['version'] ?? null);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['meta'] ?? null);
        $this->assertSame($domain, $body['source']['domain'] ?? null);
        $this->assertSame('fresh', $body['source']['state'] ?? null);
        $this->assertFalse($body['source']['stale'] ?? true);
        $this->assertIsArray($body['messages'] ?? null);
    }
}
