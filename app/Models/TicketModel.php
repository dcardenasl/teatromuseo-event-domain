<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\TicketEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class TicketModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'tickets';
    protected $primaryKey = 'id';
    protected $returnType = TicketEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['uuid', 'booking_id', 'ticket_type_id', 'holder_name', 'holder_email', 'qr_code_token', 'status', 'checked_in_at'];

    /** @var array<int, string> */
    protected array $searchableFields = ['holder_name', 'holder_email'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'booking_id', 'ticket_type_id', 'status', 'checked_in_at'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'booking_id', 'ticket_type_id', 'holder_name', 'holder_email', 'status', 'checked_in_at'];

    protected $validationRules = [
        'uuid' => 'permit_empty|string|max_length[255]|is_unique[tickets.uuid]',
        'booking_id' => 'required|is_natural_no_zero|is_not_unique[bookings.id]',
        'ticket_type_id' => 'required|is_natural_no_zero|is_not_unique[ticket_types.id]',
        'holder_name' => 'required|string|max_length[255]',
        'holder_email' => 'required|string|valid_email|max_length[255]',
        'qr_code_token' => 'permit_empty|string|max_length[255]|is_unique[tickets.qr_code_token]',
        'status' => 'required|string|max_length[255]',
        'checked_in_at' => 'permit_empty|valid_date',
    ];

    protected $beforeInsert = ['generateUuidAndQrToken'];

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateUuidAndQrToken(array $data): array
    {
        if (empty($data['data']['uuid'])) {
            $data['data']['uuid'] = \dcardenasl\Ci4ApiCore\Security\Token::generateUuid();
        }
        if (empty($data['data']['qr_code_token'])) {
            $data['data']['qr_code_token'] = \dcardenasl\Ci4ApiCore\Security\Token::generateUuid();
        }
        return $data;
    }
}
