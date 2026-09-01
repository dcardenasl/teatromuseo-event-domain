<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Every business model under app/Models must extend BaseAuditableModel so audit
 * logging is automatic. The scan is dynamic: a newly scaffolded model is covered
 * the moment it lands, without editing this test.
 */
class AuditableModelConventionsTest extends CIUnitTestCase
{
    /**
     * Models that legitimately do NOT carry an audit trail — system, log, token,
     * and join/pivot tables. A model NOT on this list must extend
     * BaseAuditableModel, so a scaffolded business entity that bypasses it fails
     * here. Adding a new entry is a conscious, reviewable decision.
     *
     * @var list<string>
     */
    private const NON_AUDITABLE = [
        'AuditLogModel',
        'MetricModel',
        'RequestLogModel',
    ];

    public function testAuditableModelsExtendSharedBaseAuditableModel(): void
    {
        $dir        = rtrim((string) ROOTPATH, DIRECTORY_SEPARATOR) . '/app/Models';
        $violations = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || ! str_ends_with($file->getFilename(), 'Model.php')) {
                continue;
            }

            $name = $file->getBasename('.php');
            if (in_array($name, self::NON_AUDITABLE, true)) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (! is_string($source) || $source === '') {
                $violations[] = "{$name}: could not read source";
                continue;
            }

            // Resolve the real class and walk the inheritance chain rather than
            // string-matching `extends BaseAuditableModel`. A model may inherit
            // audit behaviour through an intermediate core base such as
            // BaseTranslationModel or BasePublicSlugModel; a source-text check
            // cannot see through those and would report a false violation.
            $fqcn = $this->resolveClassName($source, $name);

            if ($fqcn === null || ! class_exists($fqcn)) {
                $violations[] = "{$name}: could not resolve class name";
                continue;
            }

            if ($fqcn !== BaseAuditableModel::class && ! is_subclass_of($fqcn, BaseAuditableModel::class)) {
                $violations[] = "{$name}: must extend BaseAuditableModel (directly or through a core base model), or be added to NON_AUDITABLE with rationale";
            }

            if (str_contains($source, 'use dcardenasl\Ci4ApiCore\Models\Auditable;')) {
                $violations[] = "{$name}: should not import Auditable directly";
            }
        }

        $this->assertSame([], $violations, "Auditable model convention violations:\n- " . implode("\n- ", $violations));
    }

    /**
     * Derive the fully-qualified class name from the file's own namespace
     * declaration, so the test keeps working if models are ever moved into
     * sub-namespaces.
     */
    private function resolveClassName(string $source, string $basename): ?string
    {
        if (preg_match('/^namespace\s+([^;]+);/m', $source, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]) . '\\' . $basename;
    }
}
