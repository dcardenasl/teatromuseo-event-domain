<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Project metadata (single source of truth).
 */
class Project extends BaseConfig
{
    public const NAME = 'Teatro Museo Event Domain';
    public const DESCRIPTION = 'Teatro Museo domain for scheduled programming, ticket types, reservations, and access control.';
    public const VERSION = '1.2.1';

    public string $name = 'Teatro Museo Event Domain';
    public string $description = 'Teatro Museo domain for scheduled programming, ticket types, reservations, and access control.';
    public string $version = '1.2.1';
}
