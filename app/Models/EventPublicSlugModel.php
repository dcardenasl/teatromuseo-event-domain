<?php

declare(strict_types=1);

namespace App\Models;

use dcardenasl\Ci4ApiCore\Models\BasePublicSlugModel;

/**
 * Per-locale public routing slugs for Event Domain resources.
 *
 * Only the table name is app-specific — this domain predates the core's default
 * `public_slugs` table name. Schema and audit wiring come from the core base
 * model; the validation rules stay here because the core base intentionally
 * ships none, leaving column limits to the consumer that owns the migration.
 */
class EventPublicSlugModel extends BasePublicSlugModel
{
    protected $table = 'event_public_slugs';

    protected $validationRules = [
        'resource_type' => 'required|max_length[80]',
        'resource_id'   => 'required|is_natural_no_zero',
        'locale'        => 'required|max_length[35]',
        'slug'          => 'required|max_length[191]',
    ];
}
