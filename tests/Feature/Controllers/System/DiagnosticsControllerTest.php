<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/** @internal */
final class DiagnosticsControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    private const WEB_API_KEY = 'test-web-api-key';

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        putenv('WEB_API_KEY=' . self::WEB_API_KEY);
        $_ENV['WEB_API_KEY'] = self::WEB_API_KEY;
        $_SERVER['WEB_API_KEY'] = self::WEB_API_KEY;
        $this->withHeaders(['X-App-Key' => self::WEB_API_KEY]);
    }

    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
        parent::tearDown();
    }

    public function testDiagnosticsRequiresWebAppKey(): void
    {
        $this->withHeaders([])->get('api/v1/diagnostics/public-read')->assertStatus(401);
    }

    public function testDiagnosticsReturnsSafeRuntimeSections(): void
    {
        $result = $this->get('api/v1/diagnostics/public-read');

        $result->assertStatus(200);
        $payload = json_decode((string) $result->response()->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame('success', $payload['status'] ?? null);
        $data = $payload['data'] ?? null;
        $this->assertIsArray($data);
        $this->assertSame('public-read-diagnostics.v1', $data['schema'] ?? null);
        $this->assertArrayHasKey('application', $data);
        $this->assertArrayHasKey('database', $data);
        $this->assertArrayHasKey('cache', $data);
        $this->assertArrayNotHasKey('password', $data);
    }
}
