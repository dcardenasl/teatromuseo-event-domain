<?php

declare(strict_types=1);

namespace App\Interfaces\Events;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface EventTypeServiceInterface extends CrudServiceContract
{
    public function isSlugAvailable(string $slug, string $locale, int $currentId = 0): bool;
}
