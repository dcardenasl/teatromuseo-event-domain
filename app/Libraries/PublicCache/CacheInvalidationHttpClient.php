<?php

declare(strict_types=1);

namespace App\Libraries\PublicCache;

final class CacheInvalidationHttpClient
{
    public function __construct(private readonly string $webUrl = '', private readonly string $invalidateKey = '', private readonly int $timeout = 5)
    {
    }

    /**
     * @param list<string> $scopes
     * @param list<string> $locales
     * @param list<string> $routes
     */
    public function send(array $scopes, string $source, array $locales = [], array $routes = []): bool
    {
        $url = rtrim($this->webUrl ?: (string) env('WEB_CACHE_INVALIDATE_URL', ''), '/');
        $key = $this->invalidateKey ?: (string) env('WEB_CACHE_INVALIDATE_KEY', '');
        if ($url === '' || $key === '' || $scopes === []) {
            return false;
        }
        $payload = json_encode(['scopes' => array_values(array_unique($scopes)), 'locales' => array_values(array_unique($locales)), 'routes' => array_values(array_unique($routes))]);
        if ($payload === false) {
            return false;
        }
        $curl = curl_init($url . '/cache/invalidate');
        if ($curl === false) {
            return false;
        }
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => max(1, min(10, $this->timeout)), CURLOPT_CUSTOMREQUEST => 'POST', CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Invalidate-Key: ' . $key, 'X-Cache-Invalidation-Source: ' . $source]]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $status < 200 || $status >= 300) {
            log_message('warning', '[CacheInvalidationHttpClient] Delivery failed: HTTP ' . $status . ' ' . substr($error !== '' ? $error : (string) $body, 0, 200));
            return false;
        }
        return true;
    }
}
