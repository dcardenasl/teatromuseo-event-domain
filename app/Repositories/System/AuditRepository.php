<?php

declare(strict_types=1);

namespace App\Repositories\System;

use App\Entities\AuditLogEntity;
use App\Models\AuditLogModel;
use dcardenasl\Ci4ApiCore\Repositories\AuditRepositoryInterface;
use dcardenasl\Ci4ApiCore\Repositories\BaseRepository;

/**
 * Audit Repository Implementation
 */
/** @extends BaseRepository<object> */
class AuditRepository extends BaseRepository implements AuditRepositoryInterface
{
    /** @return list<AuditLogEntity> */
    public function getByEntity(string $entityType, int $entityId): array
    {
        /** @var AuditLogModel $model */
        $model = $this->model;

        return $model->getByEntity($entityType, $entityId);
    }

    /** @return list<AuditLogEntity> */
    public function getByUser(int $userId, int $limit = 50): array
    {
        /** @var AuditLogModel $model */
        $model = $this->model;

        return $model->getByUser($userId, $limit);
    }

    /** @return list<AuditLogEntity> */
    public function getRecent(int $limit = 100): array
    {
        /** @var AuditLogModel $model */
        $model = $this->model;

        return $model->getRecent($limit);
    }

    /** @return list<array{value: string, count: int}> */
    public function getActionFacets(int $windowDays = 90, int $limit = 100): array
    {
        /** @var \App\Models\AuditLogModel $model */
        $model = $this->model;
        return array_values($model->getActionFacets($windowDays, $limit));
    }

    /** @return list<array{value: string, count: int}> */
    public function getEntityTypeFacets(int $windowDays = 90, int $limit = 100): array
    {
        /** @var \App\Models\AuditLogModel $model */
        $model = $this->model;
        return array_values($model->getEntityTypeFacets($windowDays, $limit));
    }
}
