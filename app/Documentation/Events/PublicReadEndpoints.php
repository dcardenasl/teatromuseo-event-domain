<?php

declare(strict_types=1);

namespace App\Documentation\Events;

use OpenApi\Attributes as OA;

/** OpenAPI contract for the versioned Events PublicRead surface. */
final class PublicReadEndpoints
{
    #[OA\Get(
        path: '/api/v1/public-read/{locale}/events',
        tags: ['Public Read - Events'],
        summary: 'List published events with SQL ordering',
        parameters: [
            new OA\Parameter(name: 'locale', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['agenda', 'latest', 'id', 'title'])),
            new OA\Parameter(name: 'fields', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PublicRead envelope'),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
            new OA\Response(response: 422, description: 'Invalid query'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/public-read/{locale}/events/{idOrSlug}',
        tags: ['Public Read - Events'],
        summary: 'Get one published event',
        parameters: [
            new OA\Parameter(name: 'locale', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'idOrSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PublicRead envelope'),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
            new OA\Response(response: 404, description: 'Not found or not published'),
        ]
    )]
    public function show(): void
    {
    }
}
