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

    public function testShowExcludesPublishedEventsWithoutOccurrences(): void
    {
        $event = Services::eventService(false)->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => 'Función sin horario',
            'event_type' => 'function',
            'description' => 'No debe publicarse sin una función programada.',
            'status' => 'published',
        ]))->toArray();

        $detail = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/events/' . $event['id']);
        $detail->assertStatus(404);
    }

    public function testShowResolvesTheGeneratedSlug(): void
    {
        $created = $this->createEvent('Función Viva', 'published');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get('/api/v1/public/events/funcion-viva');

        $result->assertStatus(200);
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
