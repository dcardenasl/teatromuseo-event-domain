<?php

declare(strict_types=1);

namespace App\Interfaces\Admin;

interface DashboardSummaryRepositoryInterface
{
    /**
     * @param list<string> $permissions
     * @return array<string, mixed>
     */
    public function read(array $permissions): array;
}
