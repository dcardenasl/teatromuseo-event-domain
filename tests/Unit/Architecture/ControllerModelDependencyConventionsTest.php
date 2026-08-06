<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Guardrail against Controllers bypassing the Service layer to talk to
 * Models/DB directly in teatromuseo-event-domain.
 */
class ControllerModelDependencyConventionsTest extends CIUnitTestCase
{
    /**
     * Known baseline violations. Zero-tolerance baseline.
     *
     * @var array<string, array<string, int>>
     */
    private const BASELINE = [];

    /** @var array<string, string> */
    private const PATTERNS = [
        'use_model' => '/^use\s+App\\\\Models\\\\/m',
        'model_call' => '/\bmodel\s*\(/',
        'db_connect' => '/\bDatabase\s*::\s*connect\s*\(/',
    ];

    public function testControllersDoNotGrowDirectModelOrDbCoupling(): void
    {
        $root = rtrim((string) ROOTPATH, DIRECTORY_SEPARATOR);
        $controllerDir = $root . DIRECTORY_SEPARATOR . 'app/Controllers';

        /** @var array<string, array<string, int>> $actual */
        $actual = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllerDir));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $path = $file->getPathname();
            $source = file_get_contents($path);
            if (!is_string($source) || $source === '') {
                continue;
            }

            // Strip comments and string literals
            $code = '';
            foreach (token_get_all($source) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true)) {
                    $code .= str_repeat("\n", substr_count($token[1], "\n"));
                    continue;
                }
                $code .= is_array($token) ? $token[1] : $token;
            }

            $relative = str_replace('\\', '/', ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR));

            foreach (self::PATTERNS as $name => $pattern) {
                $count = preg_match_all($pattern, $code);
                if ($count > 0) {
                    $actual[$relative][$name] = $count;
                }
            }
        }

        ksort($actual);

        $violations = [];
        foreach ($actual as $relative => $byPattern) {
            $baselineForFile = self::BASELINE[$relative] ?? null;
            if ($baselineForFile === null) {
                $violations[] = sprintf(
                    '%s: NEW file not in baseline (%s)',
                    $relative,
                    implode(', ', array_map(static fn (string $p, int $c): string => "{$p}={$c}", array_keys($byPattern), $byPattern))
                );
                continue;
            }

            foreach ($byPattern as $pattern => $count) {
                $allowed = $baselineForFile[$pattern] ?? 0;
                if ($count > $allowed) {
                    $violations[] = "{$relative}: {$pattern} count {$count} exceeds baseline {$allowed}";
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Controller layer Model/DB coupling grew beyond the baseline:\n- " . implode("\n- ", $violations) . "\n\n" .
            'Controllers must delegate to a Service or Repository. Do not bypass the layer contract.'
        );
    }
}
