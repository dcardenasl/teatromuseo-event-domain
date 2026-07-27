<?php

declare(strict_types=1);

namespace App\Libraries\References;

use InvalidArgumentException;

/**
 * Stable vocabulary for links from operational events to external domains.
 *
 * The catalog is deliberately small: it prevents ETL and API adapters from
 * inventing different strings for the same CMS relationship while keeping the
 * databases physically independent.
 */
final class ExternalReferenceCatalog
{
    public const CMS_DOMAIN = 'cms-domain';
    public const ENTRY_TYPE = 'entry';
    public const EDITORIAL_WORK = 'editorial_work';
    public const EDITORIAL_ENTRY = 'editorial_entry';

    /**
     * @return array{source_system: string, source_type: string, source_id: string, relation: string}
     */
    public static function cmsEntry(int $entryId, string $relation = self::EDITORIAL_WORK): array
    {
        if ($entryId < 1) {
            throw new InvalidArgumentException('CMS entry ID must be greater than zero.');
        }

        if (! in_array($relation, [self::EDITORIAL_WORK, self::EDITORIAL_ENTRY], true)) {
            throw new InvalidArgumentException('Unsupported CMS entry relation.');
        }

        return [
            'source_system' => self::CMS_DOMAIN,
            'source_type' => self::ENTRY_TYPE,
            'source_id' => (string) $entryId,
            'relation' => $relation,
        ];
    }
}
