<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use dcardenasl\Ci4ApiCore\Http\Filters\AbstractThrottleFilter;

/**
 * Rate-limiting filter for the domain app. Inherits fixed-window IP + user-id
 * bucketing from {@see AbstractThrottleFilter}; limits come from Config\Api
 * (`rateLimitWindow`, `rateLimitRequests`, `rateLimitUserRequests`).
 */
class ThrottleFilter extends AbstractThrottleFilter
{
    /**
     * Public GETs are server-to-server reads gated by X-App-Key. Bucket them
     * by that trusted caller rather than the hosting IP, which is shared by
     * every Web request on a shared host.
     *
     * @return list<array{key: string, limit: int, window: int}>
     */
    protected function resolveBuckets(RequestInterface $request): array
    {
        if (strtolower($request->getMethod()) === 'get' && $this->isPublicRead($request)) {
            $appKey = trim($request->getHeaderLine('X-App-Key'));

            if ($appKey !== '') {
                return [[
                    'key'    => 'rate_limit_public_read_app_' . hash('sha256', $appKey),
                    'limit'  => max(1, (int) env('PUBLIC_READ_RATE_LIMIT_REQUESTS', 600)),
                    'window' => max(1, (int) env('PUBLIC_READ_RATE_LIMIT_WINDOW', 60)),
                ]];
            }
        }

        return parent::resolveBuckets($request);
    }

    private function isPublicRead(RequestInterface $request): bool
    {
        return str_contains($request->getUri()->getPath(), '/api/v1/public/');
    }
}
