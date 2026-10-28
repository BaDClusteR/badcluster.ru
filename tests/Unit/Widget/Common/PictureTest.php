<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Widget\Common;

use BC\Widget\Common\Picture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The context of a picture: the properties with get hooks are assigned as a whole (modifying an element of such a
 * property is an Error, it made every page with pictures a 500).
 */
#[CoversClass(Picture::class)]
final class PictureTest extends TestCase {
    public function testBreakpointsFromContext(): void {
        $picture = new PictureProbe(['breakpoints' => ['0' => '500', 768 => 1000]]);

        self::assertSame([0 => 500, 768 => 1000], $picture->getBreakpoints());
    }

    public function testAltAndCaptionAreSeparate(): void {
        $picture = new PictureProbe(['alt' => 'The alt', 'caption' => 'The caption']);

        self::assertSame('The alt', $picture->getAlt());
        self::assertSame('The caption', $picture->getCaption());
    }

    public function testDefaults(): void {
        $picture = new PictureProbe([]);

        self::assertSame([], $picture->getBreakpoints());
        self::assertSame('', $picture->getAlt());
        self::assertSame('', $picture->getCaption());
    }
}

final class PictureProbe extends Picture {
    /**
     * @return array<int, int>
     */
    public function getBreakpoints(): array {
        return $this->breakpoints;
    }

    public function getAlt(): string {
        return $this->alt;
    }

    public function getCaption(): string {
        return $this->caption;
    }
}
