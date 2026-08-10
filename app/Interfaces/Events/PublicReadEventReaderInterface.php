<?php

declare(strict_types=1);

namespace App\Interfaces\Events;

use App\DTO\Request\Events\PublicReadEventRequestDTO;
use dcardenasl\Ci4ApiCore\Support\ApiResult;

interface PublicReadEventReaderInterface
{
    /** @param list<string> $fields */
    public function index(PublicReadEventRequestDTO $request, array $fields): ApiResult;

    /** @param list<string> $fields */
    public function show(string $locale, string $idOrSlug, array $fields): ApiResult;
}
