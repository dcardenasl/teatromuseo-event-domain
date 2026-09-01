<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\DTO\Request\Events\EventCreateRequestDTO;
use App\DTO\Request\Events\EventUpdateRequestDTO;
use App\DTO\Request\Events\OccurrenceCreateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;

/**
 * @internal
 */
final class EventPublicSlugIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->table('event_public_slugs')->truncate();
        $this->db->table('event_translations')->truncate();
        $this->db->table('events')->where('id >', 0)->delete();
    }

    public function testStoreGeneratesLocalizedSlugsAndExposesThemInTheResponse(): void
    {
        $service = Services::eventService(false);
        $created = $service->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => 'Función Viva',
            'event_type' => 'function',
            'description' => 'Descripción base',
            'status' => 'published',
            'translations' => [
                ['locale' => 'es', 'title' => 'Función Viva', 'description' => 'Descripción base'],
                ['locale' => 'en', 'title' => 'Live Performance', 'description' => 'Base description'],
            ],
        ]))->toArray();

        $this->assertSame(['en' => 'live-performance', 'es' => 'funcion-viva'], $created['slugs']);
        $this->assertNotSame('', $created['slug']);
    }

    public function testSlugsStayStableWhenTheTitleChanges(): void
    {
        $service = Services::eventService(false);
        $created = $service->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => 'Función Viva',
            'event_type' => 'function',
            'description' => 'Descripción base',
            'status' => 'published',
        ]))->toArray();

        $updated = $service->update((int) $created['id'], Services::requestDtoFactory()->make(EventUpdateRequestDTO::class, [
            'title' => 'Función Viva (Reestreno)',
        ]))->toArray();

        $this->assertSame($created['slugs'], $updated['slugs']);
    }

    public function testManualSlugInTranslationsPayloadWins(): void
    {
        $service = Services::eventService(false);
        $created = $service->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => 'Función Viva',
            'event_type' => 'function',
            'description' => 'Descripción base',
            'status' => 'published',
            'translations' => [
                ['locale' => 'es', 'title' => 'Función Viva', 'slug' => 'Estreno Especial'],
            ],
        ]))->toArray();

        $this->assertSame(['es' => 'estreno-especial'], $created['slugs']);
    }

    public function testGetPublicByIdOrSlugResolvesSlugUuidAndId(): void
    {
        $service = Services::eventService(false);
        $created = $service->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => 'Función Viva',
            'event_type' => 'function',
            'description' => 'Descripción base',
            'status' => 'published',
        ]))->toArray();

        Services::occurrenceService(false)->store(Services::requestDtoFactory()->make(OccurrenceCreateRequestDTO::class, [
            'event_id' => $created['id'],
            'start_time' => '2026-08-10 20:00:00',
            'end_time' => '2026-08-10 22:00:00',
            'status' => 'scheduled',
            'capacity' => 0,
            'available_spots' => 0,
        ]));

        $bySlug = $service->getPublicByIdOrSlug('funcion-viva');
        $this->assertSame((int) $created['id'], $bySlug['id']);

        $byUuid = $service->getPublicByIdOrSlug((string) $created['uuid']);
        $this->assertSame((int) $created['id'], $byUuid['id']);

        $byId = $service->getPublicByIdOrSlug((string) $created['id']);
        $this->assertSame((int) $created['id'], $byId['id']);
    }

    public function testGetPublicByIdOrSlugHidesUnpublishedEvents(): void
    {
        $service = Services::eventService(false);
        $service->store(Services::requestDtoFactory()->make(EventCreateRequestDTO::class, [
            'title' => 'Ensayo Cerrado',
            'event_type' => 'function',
            'description' => 'No público',
            'status' => 'draft',
        ]));

        $this->expectException(NotFoundException::class);
        $service->getPublicByIdOrSlug('ensayo-cerrado');
    }
}
