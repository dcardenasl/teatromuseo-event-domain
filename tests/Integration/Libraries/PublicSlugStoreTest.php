<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Models\EventPublicSlugModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use dcardenasl\Ci4ApiCore\Localization\PublicSlugStore;
use dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;

/**
 * @internal
 */
final class PublicSlugStoreTest extends CIUnitTestCase
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
    }

    public function testSyncGeneratesOneSlugPerLocaleAndKeepsExistingOnesStable(): void
    {
        $store = $this->store();

        $store->syncForResource('event', 10, ['es' => 'Función Viva', 'en' => 'Live Performance']);

        $this->assertSame(
            ['en' => 'live-performance', 'es' => 'funcion-viva'],
            $store->slugsForResource('event', 10)
        );

        // A title change must not move the published URL.
        $store->syncForResource('event', 10, ['es' => 'Función Viva (Reestreno)', 'en' => 'Live Performance Redux']);

        $this->assertSame(
            ['en' => 'live-performance', 'es' => 'funcion-viva'],
            $store->slugsForResource('event', 10)
        );
    }

    public function testSyncUniquifiesCollidingSlugsAcrossResources(): void
    {
        $store = $this->store();

        $store->syncForResource('event', 10, ['es' => 'Función Viva']);
        $store->syncForResource('event', 11, ['es' => 'Función Viva']);

        $this->assertSame(['es' => 'funcion-viva'], $store->slugsForResource('event', 10));
        $this->assertSame(['es' => 'funcion-viva-2'], $store->slugsForResource('event', 11));
    }

    public function testManualSlugOverridesTheGeneratedOne(): void
    {
        $store = $this->store();

        $store->syncForResource('event', 10, ['es' => 'Función Viva']);
        $store->syncForResource('event', 10, ['es' => 'Función Viva'], ['es' => 'Estreno Especial']);

        $this->assertSame(['es' => 'estreno-especial'], $store->slugsForResource('event', 10));
    }

    public function testResolveResourceIdPrefersTheRequestedLocale(): void
    {
        // "gala" is a valid slug in two locales pointing at different events.
        $store = $this->store('fr');
        $store->syncForResource('event', 10, [], ['es' => 'gala']);
        $store->syncForResource('event', 11, [], ['fr' => 'gala']);

        $this->assertSame(11, $store->resolveResourceId('event', 'gala'));
        $this->assertNull($store->resolveResourceId('event', 'missing-slug'));
    }

    public function testResolveResourceIdFallsBackToAnyLocale(): void
    {
        $store = $this->store('pt');
        $store->syncForResource('event', 10, ['es' => 'Función Viva']);

        $this->assertSame(10, $store->resolveResourceId('event', 'funcion-viva'));
    }

    public function testResolveSlugPicksRequestedLocaleThenFallback(): void
    {
        $store = $this->store('en');

        $this->assertSame('live', $store->resolveSlug(['es' => 'vivo', 'en' => 'live']));
        $this->assertSame('vivo', $this->store('de')->resolveSlug(['es' => 'vivo', 'pt' => 'vivo-pt']));
        $this->assertSame('', $store->resolveSlug([]));
    }

    private function store(?string $acceptLanguage = null): PublicSlugStore
    {
        $request = null;
        if ($acceptLanguage !== null) {
            $request = $this->createMock(IncomingRequest::class);
            $request->method('getHeaderLine')->with('Accept-Language')->willReturn($acceptLanguage);
        }

        return new PublicSlugStore(
            new EventPublicSlugModel(),
            new SlugGenerator(),
            new RequestLocaleResolver($request),
        );
    }
}
