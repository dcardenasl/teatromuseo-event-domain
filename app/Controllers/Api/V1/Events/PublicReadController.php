<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\PublicReadEventRequestDTO;
use App\DTO\Request\Events\PublicReadLocaleRequestDTO;
use App\Interfaces\Events\PublicReadEventReaderInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;
use dcardenasl\Ci4ApiCore\Traits\SparseFieldsetTrait;

final class PublicReadController extends ApiController
{
    use SparseFieldsetTrait;

    /** @var list<string> */
    private const LISTING_FIELDS = [
        'id', 'uuid', 'title', 'event_type', 'slug', 'cover_file_id',
        'cover_image', 'localized', 'next_occurrence_at', 'status',
    ];

    /** @var list<string> */
    private const DETAIL_FIELDS = [
        'id', 'uuid', 'title', 'event_type', 'description', 'slug', 'slugs',
        'cover_file_id', 'cover_image', 'gallery_file_ids', 'gallery_images',
        'translations', 'localized', 'occurrences', 'status', 'created_at',
        'updated_at',
    ];

    private PublicReadEventReaderInterface $reader;

    protected function resolveDefaultService(): object
    {
        $this->reader = Services::publicReadEventReader();

        return $this->reader;
    }

    public function index(string $locale): ResponseInterface
    {
        return $this->handleRequest(
            function (PublicReadEventRequestDTO $dto, SecurityContext $context): mixed {
                return $this->reader->index($dto, $this->parseFieldsParam(self::LISTING_FIELDS));
            },
            PublicReadEventRequestDTO::class,
            ['locale' => $locale],
        );
    }

    public function show(string $locale, string $idOrSlug): ResponseInterface
    {
        return $this->handleRequest(
            fn (PublicReadLocaleRequestDTO $dto): mixed => $this->reader->show($dto->locale, $idOrSlug, $this->parseFieldsParam(self::DETAIL_FIELDS)),
            PublicReadLocaleRequestDTO::class,
            ['locale' => $locale],
        );
    }
}
