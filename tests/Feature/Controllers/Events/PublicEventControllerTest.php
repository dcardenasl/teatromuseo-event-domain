<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Events;

use App\DTO\Request\Events\EventCreateRequestDTO;
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
        return Services::eventService(false)->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => $title,
            'event_type' => 'function',
            'description' => 'Descripción de ' . $title,
            'status' => $status,
            'start_time' => $startTime,
        ]))->toArray();
    }
}
