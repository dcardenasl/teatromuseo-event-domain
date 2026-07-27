<?php

declare(strict_types=1);

namespace App\Models;

use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class EventTranslationModel extends BaseAuditableModel
{
    protected $table = 'event_translations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
    ];
}
