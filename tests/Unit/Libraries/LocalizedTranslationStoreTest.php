<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Models\EventTranslationModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;
use dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver;

/**
 * @internal
 */
final class LocalizedTranslationStoreTest extends CIUnitTestCase
{
    private LocalizedTranslationStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        // No request: the resolver yields no preferred locale, so resolve()
        // falls back to Config\Localization::$legacyFallbackLocale.
        $this->store = new LocalizedTranslationStore(
            new EventTranslationModel(),
            new RequestLocaleResolver(null),
            config('Localization'),
        );
    }

    public function testNormalizesLocaleKeyedAndListPayloads(): void
    {
        $rows = $this->store->normalize('event', [
            'es' => [
                'title' => 'Festival de invierno',
                'description' => 'Descripción',
            ],
            [
                'locale' => 'FR-fr',
                'title' => 'Festival d’hiver',
            ],
        ]);

        $this->assertSame([
            [
                'locale' => 'es',
                'fields' => [
                    'title' => 'Festival de invierno',
                    'description' => 'Descripción',
                ],
            ],
            [
                'locale' => 'fr-fr',
                'fields' => ['title' => 'Festival d’hiver'],
            ],
        ], $rows);
    }

    public function testRejectsUnknownFieldsAndDuplicateLocales(): void
    {
        $this->expectException(BadRequestException::class);
        $this->store->normalize('event', [
            ['locale' => 'es', 'title' => 'Uno'],
            ['locale' => 'ES', 'description' => 'Dos'],
        ]);
    }

    public function testRejectsUnsupportedResourceAndInvalidLocale(): void
    {
        $this->expectException(BadRequestException::class);
        $this->store->normalize('unknown', [['locale' => 'es', 'title' => 'No']]);
    }

    public function testResolvePrefersTheAcceptLanguageHeaderOverTheFallbackLocale(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getHeaderLine')->with('Accept-Language')->willReturn('en');

        $store = new LocalizedTranslationStore(
            new EventTranslationModel(),
            new RequestLocaleResolver($request),
            config('Localization'),
        );

        $resolved = $store->resolve('event', [
            ['locale' => 'es', 'fields' => ['title' => 'Festival de invierno', 'description' => 'Descripción']],
            ['locale' => 'en', 'fields' => ['title' => 'Winter festival', 'description' => 'Description']],
        ], []);

        $this->assertSame('en', $resolved['locale']);
        $this->assertSame('Winter festival', $resolved['title']);
    }

    public function testResolveFallsBackToTheLegacyLocaleWithoutARequest(): void
    {
        $resolved = $this->store->resolve('event', [
            ['locale' => 'es', 'fields' => ['title' => 'Festival de invierno', 'description' => 'Descripción']],
        ], []);

        $this->assertSame('es', $resolved['locale']);
        $this->assertSame('Festival de invierno', $resolved['title']);
    }
}
