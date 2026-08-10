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
        $this->assertTrue($body['ok'] ?? false);
        $this->assertSame(1, $body['version'] ?? null);
        $this->assertSame('events', $body['source']['domain'] ?? null);
        $this->assertSame('PublicRead Agenda', $body['data'][0]['title'] ?? null);
        $this->assertNotEmpty($body['data'][0]['next_occurrence_at'] ?? null);
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
}
