<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\BookingEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class BookingModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $returnType = BookingEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['uuid', 'user_id', 'guest_email', 'total_amount', 'status', 'reserved_until'];

    /** @var array<int, string> */
    protected array $searchableFields = ['guest_email'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'user_id', 'total_amount', 'status', 'reserved_until'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'user_id', 'guest_email', 'total_amount', 'status', 'reserved_until'];

    protected $validationRules = [
        'uuid' => 'permit_empty|string|max_length[255]|is_unique[bookings.uuid]',
        'user_id' => 'permit_empty|integer',
        'guest_email' => 'permit_empty|string|valid_email|max_length[255]',
        'total_amount' => 'required|decimal',
        'status' => 'required|string|max_length[255]',
        'reserved_until' => 'permit_empty|valid_date',
    ];

    protected $beforeInsert = ['generateUuid'];

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateUuid(array $data): array
    {
        if (empty($data['data']['uuid'])) {
            $data['data']['uuid'] = \dcardenasl\Ci4ApiCore\Security\Token::generateUuid();
        }
        return $data;
    }
}
