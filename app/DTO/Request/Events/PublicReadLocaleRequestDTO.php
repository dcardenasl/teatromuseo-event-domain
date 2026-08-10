<?php

declare(strict_types=1);

namespace App\DTO\Request\Events;

use CodeIgniter\Validation\ValidationInterface;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

/** Common locale contract for Events PublicRead detail routes. */
readonly class PublicReadLocaleRequestDTO extends BaseRequestDTO
{
    public function __construct(array $data, ?ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);
        $this->locale = strtolower(trim((string) ($data['locale'] ?? '')));
    }

    public string $locale;

    public function rules(): array
    {
        return ['locale' => 'required|regex_match[/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/i]'];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
    }

    /** @return array{locale: string} */
    public function toArray(): array
    {
        return ['locale' => $this->locale];
    }
}
