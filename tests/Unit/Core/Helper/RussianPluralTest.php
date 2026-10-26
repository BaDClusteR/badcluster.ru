<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Helper;

use BC\Core\Helper\RussianPlural;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RussianPlural::class)]
final class RussianPluralTest extends TestCase {
    /**
     * @return iterable<array{int, string}>
     */
    public static function provideCounts(): iterable {
        yield [0, 'ошибок'];
        yield [1, 'ошибка'];
        yield [2, 'ошибки'];
        yield [3, 'ошибки'];
        yield [4, 'ошибки'];
        yield [5, 'ошибок'];
        yield [9, 'ошибок'];
        yield [10, 'ошибок'];
        yield [11, 'ошибок'];
        yield [12, 'ошибок'];
        yield [13, 'ошибок'];
        yield [14, 'ошибок'];
        yield [15, 'ошибок'];
        yield [20, 'ошибок'];
        yield [21, 'ошибка'];
        yield [22, 'ошибки'];
        yield [24, 'ошибки'];
        yield [25, 'ошибок'];
        yield [100, 'ошибок'];
        yield [101, 'ошибка'];
        yield [111, 'ошибок'];
        yield [112, 'ошибок'];
        yield [114, 'ошибок'];
        yield [121, 'ошибка'];
        yield [1002, 'ошибки'];
        yield [-1, 'ошибка'];
        yield [-2, 'ошибки'];
        yield [-11, 'ошибок'];
        yield [-21, 'ошибка'];
    }

    #[DataProvider('provideCounts')]
    public function testForm(int $count, string $expected): void {
        self::assertSame($expected, RussianPlural::form($count, 'ошибка', 'ошибки', 'ошибок'));
    }
}
