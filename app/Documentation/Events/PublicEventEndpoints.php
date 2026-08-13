<?php

declare(strict_types=1);

namespace App\Documentation\Events;

use OpenApi\Attributes as OA;

/**
 * OpenAPI definitions for the public (web app key) Event endpoints.
 */
class PublicEventEndpoints
{
    #[OA\Get(
        path: '/api/v1/public/events/types',
        tags: ['Public Events'],
        summary: 'List active event types for public filters',
        description: 'Returns the administrable event-type catalogue with localized names. Only active types are exposed.',
        security: [['appKeyAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/EventTypeResponse')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
        ]
    )]
    public function types(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/public/events/{idOrSlug}',
        tags: ['Public Events'],
        summary: 'Get a published event by id, uuid, or per-locale routing slug',
        description: 'Slug resolution prefers the Accept-Language locale and falls back to any locale, so shared URLs keep working across languages.',
        security: [['appKeyAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'idOrSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Found',
                content: new OA\JsonContent(ref: '#/components/schemas/EventResponse')
            ),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
            new OA\Response(response: 404, description: 'Not found or not published'),
        ]
    )]
    public function show(): void
    {
    }
}
