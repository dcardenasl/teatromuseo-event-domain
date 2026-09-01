<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Libraries\References\ExternalReferenceCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExternalReferenceCatalogTest extends TestCase
{
    public function testBuildsStableCmsWorkReference(): void
    {
        $this->assertSame([
            'source_system' => 'cms-domain',
            'source_type' => 'entry',
            'source_id' => '42',
            'relation' => 'editorial_work',
        ], ExternalReferenceCatalog::cmsEntry(42));
    }

    public function testRejectsInvalidCmsEntryId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExternalReferenceCatalog::cmsEntry(0);
    }

    public function testRejectsUnknownRelation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExternalReferenceCatalog::cmsEntry(42, 'unknown');
    }
}
