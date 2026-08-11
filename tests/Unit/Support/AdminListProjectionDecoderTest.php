<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\AdminListProjectionDecoder;
use PHPUnit\Framework\TestCase;

/** @internal */
final class AdminListProjectionDecoderTest extends TestCase
{
    public function testMalformedHexFragmentsAreIgnoredWithoutThrowing(): void
    {
        $translations = AdminListProjectionDecoder::translations('es:title:4869|en:title:ABC');
        $slugs = AdminListProjectionDecoder::slugs('es:736c|en:ABC');

        $this->assertSame([
            ['locale' => 'es', 'fields' => ['title' => 'Hi']],
        ], $translations);
        $this->assertSame(['es' => 'sl'], $slugs);
    }
}
