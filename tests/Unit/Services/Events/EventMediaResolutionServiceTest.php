<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Libraries\Hub\HubClient;
use App\Services\Events\EventMediaResolutionService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EventMediaResolutionServiceTest extends CIUnitTestCase
{
    public function testResolveMediaFieldsDoesNotCallHubWhenNoFileIdsPresent(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->expects($this->never())->method('resolvePublicFileMeta');

        $service = new EventMediaResolutionService($hubClient);

        $result = $service->resolveMediaFields(['id' => 1, 'title' => 'Función Viva']);

        $this->assertNull($result['cover_image']);
        $this->assertSame([], $result['gallery_images']);
    }

    public function testResolveMediaFieldsResolvesCoverAndGalleryFromHubMetadata(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->expects($this->once())
            ->method('resolvePublicFileMeta')
            ->with([10, 20, 30])
            ->willReturn([
                10 => ['url' => 'https://hub.test/files/10.jpg', 'variants' => ['thumb' => 'https://hub.test/files/10-thumb.jpg']],
                20 => ['url' => 'https://hub.test/files/20.jpg', 'variants' => null],
                30 => ['url' => 'https://hub.test/files/30.jpg', 'variants' => '{"thumb":"https://hub.test/files/30-thumb.jpg"}'],
            ]);

        $service = new EventMediaResolutionService($hubClient);

        $result = $service->resolveMediaFields([
            'id' => 1,
            'title' => 'Función Viva',
            'cover_file_id' => 10,
            'gallery_file_ids' => '20, 30',
        ]);

        $this->assertSame([
            'source_kind' => 'hub_file',
            'file_id'     => 10,
            'url'         => 'https://hub.test/files/10.jpg',
            'variants'    => ['thumb' => 'https://hub.test/files/10-thumb.jpg'],
        ], $result['cover_image']);

        $this->assertSame([
            [
                'source_kind' => 'hub_file',
                'file_id'     => 20,
                'url'         => 'https://hub.test/files/20.jpg',
                'variants'    => null,
            ],
            [
                'source_kind' => 'hub_file',
                'file_id'     => 30,
                'url'         => 'https://hub.test/files/30.jpg',
                'variants'    => ['thumb' => 'https://hub.test/files/30-thumb.jpg'],
            ],
        ], $result['gallery_images']);
    }

    public function testResolveMediaFieldsSkipsIdsWithNoHubMetadata(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->method('resolvePublicFileMeta')->willReturn([]);

        $service = new EventMediaResolutionService($hubClient);

        $result = $service->resolveMediaFields([
            'cover_file_id' => 99,
            'gallery_file_ids' => '98,97',
        ]);

        $this->assertNull($result['cover_image']);
        $this->assertSame([], $result['gallery_images']);
    }

    public function testResolveMediaFieldsIgnoresInvalidGalleryIds(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->expects($this->once())
            ->method('resolvePublicFileMeta')
            ->with([5])
            ->willReturn([5 => ['url' => 'https://hub.test/files/5.jpg', 'variants' => null]]);

        $service = new EventMediaResolutionService($hubClient);

        $result = $service->resolveMediaFields([
            'gallery_file_ids' => '5,0,-1,abc,',
        ]);

        $this->assertSame([
            [
                'source_kind' => 'hub_file',
                'file_id'     => 5,
                'url'         => 'https://hub.test/files/5.jpg',
                'variants'    => null,
            ],
        ], $result['gallery_images']);
    }
}
