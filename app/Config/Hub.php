<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Hub configuration — coordinates with the Teatro Museo API hub.
 *
 * The hub owns auth, IAM, users, files. Each domain app delegates JWT validation
 * to the hub via POST /api/v1/auth/introspect and obtains its own service token
 * via POST /api/v1/auth/service-token.
 */
class Hub extends BaseConfig
{
    /**
     * Base URL of the hub (no trailing slash). e.g. http://localhost:8180
     */
    public string $url = '';

    /**
     * App-key used in the X-App-Key header for hub calls. Created from the hub
     * with `php spark apps:bootstrap <code>` (which also creates the API Key
     * bound to the application).
     */
    public string $apiKey = '';

    /**
     * Domain app code as registered in the hub (matches the application code).
     */
    public string $appCode = '';

    /**
     * Cache TTL (seconds) for /auth/introspect responses keyed by JTI.
     * Lower = fresher revocation; higher = less load on the hub.
     */
    public int $introspectCacheTtl = 60;

    /**
     * Refresh the cached service token this many seconds before its expiry.
     */
    public int $serviceTokenSafetyMargin = 30;

    /**
     * Hard timeout (seconds) for HTTP calls to the hub.
     */
    public int $httpTimeout = 5;

    /**
     * Hub endpoint paths. Override here to point at a different hub API version
     * without forking the HubClient.
     */
    public string $introspectPath   = '/api/v1/auth/introspect';
    public string $serviceTokenPath = '/api/v1/auth/service-token';
    public string $permissionsPath  = '/api/v1/iam/permissions';

    /**
     * Optional superadmin JWT for setup-only operations (currently
     * `php spark domain:sync-permissions`). Empty by default. The CLI flag
     * `--admin-token=<jwt>` takes precedence over this value.
     *
     * Permission registration hits `/api/v1/iam/permissions`, which the hub
     * gates on `iam.superadmin-access` — service tokens cannot pass that
     * filter, so a human-issued superadmin JWT is required for setup.
     */
    public string $adminToken = '';

    /**
     * Shared secret used to verify HMAC-signed calls the Hub makes *into*
     * this domain app (internal/files/* usage-check and invalidate-cache
     * routes — see HubSignatureFilter). Configured identically on the Hub
     * and every domain app. Optional: unset disables those routes (they
     * fail closed), it does not fail application boot like hub.url/apiKey.
     */
    public string $internalSecret = '';

    public function __construct()
    {
        parent::__construct();
        $this->url        = (string) (env('hub.url') ?: $this->url);
        $this->apiKey     = (string) (env('hub.apiKey') ?: $this->apiKey);
        $this->appCode    = (string) (env('hub.appCode') ?: $this->appCode);
        $this->adminToken = (string) (env('hub.adminToken') ?: $this->adminToken);
        $this->internalSecret = (string) (env('HUB_INTERNAL_SECRET') ?: env('hub.internalSecret') ?: '');

        $ttl = env('hub.introspectCacheTtl');
        if ($ttl !== null && $ttl !== false && $ttl !== '') {
            $this->introspectCacheTtl = (int) $ttl;
        }
    }
}
