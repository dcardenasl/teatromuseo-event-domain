<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;

/**
 * @internal
 */
final class SlugGeneratorTest extends CIUnitTestCase
{
    private SlugGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new SlugGenerator();
    }

    public function testSlugifyTransliteratesAccentsAndCollapsesSeparators(): void
    {
        $this->assertSame('funcion-viva', $this->generator->slugify('Función Viva'));
        $this->assertSame('festival-d-hiver', $this->generator->slugify("Festival d'hiver"));
        $this->assertSame('taller-de-escena-2026', $this->generator->slugify('  Taller   de Escena — 2026!  '));
    }

    public function testSlugifyReturnsEmptyStringForEmptyInput(): void
    {
        $this->assertSame('', $this->generator->slugify(''));
        $this->assertSame('', $this->generator->slugify('   '));
    }

    public function testUniquifyReturnsBaseSlugWhenAvailable(): void
    {
        $slug = $this->generator->uniquify('funcion-viva', static fn (string $candidate): bool => true);

        $this->assertSame('funcion-viva', $slug);
    }

    public function testUniquifyAppendsNumericSuffixOnCollision(): void
    {
        $taken = ['funcion-viva', 'funcion-viva-2'];
        $slug = $this->generator->uniquify(
            'funcion-viva',
            static fn (string $candidate): bool => ! in_array($candidate, $taken, true)
        );

        $this->assertSame('funcion-viva-3', $slug);
    }

    public function testUniquifyReturnsEmptyForEmptyBase(): void
    {
        $this->assertSame('', $this->generator->uniquify('', static fn (string $candidate): bool => true));
    }
}
