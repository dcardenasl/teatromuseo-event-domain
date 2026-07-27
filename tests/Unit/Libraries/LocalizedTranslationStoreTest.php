<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Libraries\Localization\LocalizedTranslationStore;
use App\Models\EventTranslationModel;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;

/**
 * @internal
 */
final class LocalizedTranslationStoreTest extends CIUnitTestCase
{
    private LocalizedTranslationStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new LocalizedTranslationStore(new EventTranslationModel());
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
}
