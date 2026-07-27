<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\DTO\Request\Events\EventCreateRequestDTO;
use App\DTO\Request\Events\EventUpdateRequestDTO;
use App\Models\EventTranslationModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class EventTranslationServiceIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testStoresTranslationsAndSupportsTranslationOnlyUpdates(): void
    {
        $dtoFactory = Services::requestDtoFactory();
        $service = Services::eventService(false);

        $created = $service->store($dtoFactory->make(EventCreateRequestDTO::class, [
            'title' => 'Festival de invierno',
            'event_type' => 'festival',
            'description' => 'Descripción base',
            'status' => 'draft',
            'translations' => [
                ['locale' => 'es', 'title' => 'Festival de invierno', 'description' => 'Descripción base'],
                ['locale' => 'fr', 'title' => 'Festival d’hiver', 'description' => 'Description française'],
                ['locale' => 'pt', 'title' => 'Festival de inverno'],
            ],
        ]));

        $createdData = $created->toArray();
        $this->assertNotEmpty($createdData['uuid']);
        $this->assertSame(3, count($createdData['translations']));
        $this->assertArrayHasKey('title', $createdData['translations'][0]);
        $this->assertArrayNotHasKey('fields', $createdData['translations'][0]);
        $this->assertSame('Festival de invierno', $createdData['localized']['title']);

        $eventId = (int) $createdData['id'];
        $updated = $service->update($eventId, $dtoFactory->make(EventUpdateRequestDTO::class, [
            'translations' => [
                ['locale' => 'fr', 'title' => 'Festival d’hiver 2026', 'description' => 'Nouvelle description'],
            ],
        ]));

        $updatedData = $updated->toArray();
        $this->assertSame('Festival d’hiver 2026', $this->translationValue($updatedData, 'fr', 'title'));
        $this->assertSame('Festival de invierno', $this->translationValue($updatedData, 'es', 'title'));

        $rows = model(EventTranslationModel::class)
            ->where('translatable_type', 'event')
            ->where('translatable_id', $eventId)
            ->findAll();
        $this->assertCount(5, $rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function translationValue(array $data, string $locale, string $field): string
    {
        foreach ($data['translations'] ?? [] as $row) {
            if (($row['locale'] ?? '') !== $locale) {
                continue;
            }

            if (isset($row['fields']) && is_array($row['fields'])) {
                return (string) ($row['fields'][$field] ?? '');
            }

            return (string) ($row[$field] ?? '');
        }

        return '';
    }
}
