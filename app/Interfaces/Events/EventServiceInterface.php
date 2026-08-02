<?php

declare(strict_types=1);

namespace App\Interfaces\Events;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface EventServiceInterface extends CrudServiceContract
{
    /**
     * Public detail lookup by numeric id, uuid, or per-locale routing slug.
     * Only published events resolve; anything else raises NotFoundException.
     *
     * @return array<string, mixed>
     */
    public function getPublicByIdOrSlug(string $idOrSlug): array;

    /**
     * Public "Cartelera" listing order: upcoming events first (soonest first), then past
     * events (most recent first) — the natural reading order for a live billboard, which a
     * single ascending/descending `sort` can't express. Scoped to the public listing only;
     * the generic index() used by the admin CRUD keeps its normal single-direction sort.
     */
    public function indexPublicCartelera(DataTransferObjectInterface $request, ?SecurityContext $context = null): DataTransferObjectInterface;
}
