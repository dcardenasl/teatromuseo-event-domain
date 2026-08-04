<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EventTypeIndexRequest')]
readonly class EventTypeIndexRequestDTO extends BaseRequestDTO
{
    public int $page;
    public int $per_page;
    public ?string $search;
    public string $sort;
    /** @var array<string, mixed> */
    public array $filter;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'page'      => 'permit_empty|is_natural_no_zero',
            'per_page'  => 'permit_empty|is_natural_no_zero|less_than[101]',
            'search'    => 'permit_empty|string|max_length[100]',
            'sort'      => 'permit_empty|max_length[100]',
            'filter'    => 'permit_empty',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->page = isset($data['page']) ? (int) $data['page'] : 1;
        $this->per_page = isset($data['per_page']) ? (int) $data['per_page'] : 20;
        $this->search = $data['search'] ?? null;
        $this->sort = (string) ($data['sort'] ?? '');
        $this->filter = is_array($data['filter'] ?? null) ? $data['filter'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->per_page,
            'search' => $this->search,
            'sort' => $this->sort,
            'filter' => $this->filter,
        ];
    }
}
