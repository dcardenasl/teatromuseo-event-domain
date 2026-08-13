<?php

declare(strict_types=1);

namespace App\Interfaces\Events;

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
}
