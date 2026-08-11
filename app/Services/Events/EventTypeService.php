<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\DTO\Response\Events\EventTypeResponseDTO;
use App\Entities\EventTypeEntity;
use App\Interfaces\Events\AdminListProjectionRepositoryInterface;
use App\Interfaces\Events\EventTypeServiceInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use App\Models\EventPublicSlugModel;
use App\Support\AdminListProjectionDecoder;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\PaginatedResponseDTO;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Localization\PublicSlugStore;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;
use dcardenasl\Ci4ApiCore\Services\HasLocalizedTranslations;
use dcardenasl\Ci4ApiCore\Services\HasPublicSlugs;

/**
 * @extends BaseCrudService<EventTypeEntity>
 */
class EventTypeService extends BaseCrudService implements EventTypeServiceInterface
{
    use HasLocalizedTranslations {
        beforeStore as private localizedBeforeStore;
        afterStore as private localizedAfterStore;
        beforeUpdate as private localizedBeforeUpdate;
        afterUpdate as private localizedAfterUpdate;
        enrichEntities as private localizedEnrichEntities;
        mapToResponse as private localizedMapToResponse;
    }
    use HasPublicSlugs;

    /**
     * @param RepositoryInterface<EventTypeEntity> $eventTypeRepository
     */
    public function __construct(
        RepositoryInterface $eventTypeRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        PublicSlugStore $slugStore,
        private EventPublicSlugModel $publicSlugModel,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
        private readonly ?AdminListProjectionRepositoryInterface $eventTypeListRepository = null,
    ) {
        parent::__construct($eventTypeRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'event_type';
        $this->slugStore = $slugStore;
        $this->slugResourceType = 'event_type';
        $this->slugSourceField = 'name';
    }

    public function index(DataTransferObjectInterface $request, ?SecurityContext $context = null): DataTransferObjectInterface
    {
        $requestData = $request->toArray();
        if (($requestData['projection'] ?? 'full') !== 'list' || $this->eventTypeListRepository === null) {
            return parent::index($request, $context);
        }

        $result = $this->eventTypeListRepository->paginateAdminList($requestData, (int) ($requestData['page'] ?? 1), (int) ($requestData['per_page'] ?? 20));
        $data = array_map(static function (array $row): EventTypeResponseDTO {
            $decoded = AdminListProjectionDecoder::translations($row['translations_data'] ?? null);
            $row['translations'] = array_map(static fn (array $translation): array => [
                'locale' => $translation['locale'],
                ...$translation['fields'],
            ], $decoded);
            $row['slugs'] = AdminListProjectionDecoder::slugs($row['slugs_data'] ?? null);
            $row['localized'] = ['name' => (string) ($row['name'] ?? '')];
            unset($row['translations_data'], $row['slugs_data'], $row['total_items']);

            return EventTypeResponseDTO::fromArray($row);
        }, $result['data']);

        return PaginatedResponseDTO::fromArray(['data' => $data, 'total' => $result['total'], 'page' => $result['page'], 'per_page' => $result['per_page']]);
    }

    public function isSlugAvailable(string $slug, string $locale, int $currentId = 0): bool
    {
        $slug = (new SlugGenerator())->slugify($slug);
        $locale = strtolower(trim($locale));
        if ($slug === '' || $locale === '') {
            return false;
        }

        $query = $this->publicSlugModel
            ->where('resource_type', 'event_type')
            ->where('locale', $locale)
            ->where('slug', $slug);
        if ($currentId > 0) {
            $query->where('resource_id !=', $currentId);
        }

        return $query->countAllResults() === 0;
    }

    /** @param array<string, mixed> $data */
    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeStore($data, $context);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterStore($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['event_types', 'events']);
    }

    /** @param array<string, mixed> $data */
    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeUpdate($id, $data, $context);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterUpdate($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['event_types', 'events']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['event_types', 'events']);
    }

    /** @param array<int, object> $entities */
    protected function enrichEntities(array $entities): array
    {
        return $this->attachSlugs($this->localizedEnrichEntities($entities));
    }

    protected function mapToResponse(object $entity): DataTransferObjectInterface
    {
        $this->attachSlugsToEntity($entity);

        return $this->localizedMapToResponse($entity);
    }
}
