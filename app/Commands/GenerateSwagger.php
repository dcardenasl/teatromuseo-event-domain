<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use OpenApi\Annotations\OpenApi;

class GenerateSwagger extends BaseCommand
{
    protected $group       = 'API';
    protected $name        = 'swagger:generate';
    protected $description = 'Generate OpenAPI/Swagger documentation';
    protected $usage       = 'swagger:generate';

    public function run(array $params): int
    {
        CLI::write('Generating OpenAPI documentation...', 'yellow');

        try {
            $appPath = APPPATH;
            $outputPath = FCPATH . 'swagger.json';

            // Scan directories for OpenAPI annotations
            $openapi = (new \OpenApi\Generator())
                ->generate([
                    $appPath . 'Config/OpenApi.php',
                    $appPath . 'Controllers/',
                    $appPath . 'Documentation/',
                    $appPath . 'DTO/',
                ]);

            if (! $openapi instanceof OpenApi) {
                throw new \RuntimeException('OpenAPI generator returned no document.');
            }

            // Write to file
            if (file_put_contents($outputPath, $openapi->toJson()) === false) {
                throw new \RuntimeException('Unable to write the generated OpenAPI document.');
            }

            // Calculate statistics (components properties may be UNDEFINED sentinel when empty)
            $document = get_object_vars($openapi);
            $paths = is_array($document['paths'] ?? null) ? $document['paths'] : [];
            $components = $document['components'] ?? null;
            $componentData = is_object($components) ? get_object_vars($components) : [];
            $schemas = is_array($componentData['schemas'] ?? null) ? $componentData['schemas'] : [];
            $responses = is_array($componentData['responses'] ?? null) ? $componentData['responses'] : [];
            $requestBodies = is_array($componentData['requestBodies'] ?? null) ? $componentData['requestBodies'] : [];
            $endpointCount = count($paths);
            $schemaCount = count($schemas);
            $responseCount = count($responses);
            $requestBodyCount = count($requestBodies);

            CLI::write('OpenAPI documentation generated successfully!', 'green');
            CLI::write('Location: ' . $outputPath, 'green');
            CLI::write('', '');
            CLI::write('Statistics:', 'cyan');
            CLI::write('  Endpoints: ' . $endpointCount, 'white');
            CLI::write('  Schemas: ' . $schemaCount, 'white');
            CLI::write('  Reusable Responses: ' . $responseCount, 'white');
            CLI::write('  Request Bodies: ' . $requestBodyCount, 'white');
            CLI::write('', '');
            CLI::write('You can view it at: http://localhost:8193/swagger.json', 'cyan');
        } catch (\Throwable $e) {
            CLI::error('Failed to generate OpenAPI documentation');
            CLI::error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
