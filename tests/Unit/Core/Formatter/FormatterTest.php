<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Formatter;

use BC\Core\Formatter\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Formatter::class)]
final class FormatterTest extends TestCase {
    /**
     * @return iterable<array{int, string}>
     */
    public static function provideSizes(): iterable {
        yield [0, '0 байт'];
        yield [1, '1 байт'];
        yield [10, '10 байт'];
        yield [100, '100 байт'];
        yield [1000, '1000 байт'];
        yield [1023, '1023 байт'];
        yield [1024, '1 Кб'];
        yield [1536, '1.5 Кб'];
        yield [1100, '1.07 Кб'];
        yield [10 * 1024, '10 Кб'];
        yield [100 * 1024, '100 Кб'];
        yield [1000 * 1024, '1000 Кб'];
        yield [1024 ** 2, '1 Мб'];
        yield [(int)(2.25 * 1024 ** 2), '2.25 Мб'];
        yield [20 * 1024 ** 2, '20 Мб'];
        yield [1024 ** 3, '1 Гб'];
        yield [1024 ** 4, '1 Тб'];
        yield [1024 ** 5, '1 Пб'];
        yield [1024 ** 6, '1 Эб'];
        // The last postfix is the biggest one.
        yield [PHP_INT_MAX, '8 Эб'];
    }

    #[DataProvider('provideSizes')]
    public function testHumanReadableSize(int $bytes, string $expected): void {
        self::assertSame($expected, new Formatter()->formatAsHumanReadableSize($bytes));
    }

    /**
     * @return iterable<array{int, string}>
     */
    public static function provideDurations(): iterable {
        yield [0, '0:00'];
        yield [5, '0:05'];
        yield [59, '0:59'];
        yield [60, '1:00'];
        yield [61, '1:01'];
        yield [599, '9:59'];
        yield [600, '10:00'];
        yield [3599, '59:59'];
        yield [3600, '1:00:00'];
        yield [3601, '1:00:01'];
        yield [3660, '1:01:00'];
        yield [3661, '1:01:01'];
        yield [5400, '1:30:00'];
        yield [7199, '1:59:59'];
        yield [36000 + 125, '10:02:05'];
    }

    #[DataProvider('provideDurations')]
    public function testHumanReadableDuration(int $seconds, string $expected): void {
        self::assertSame($expected, new Formatter()->formatAsHumanReadableDuration($seconds));
    }

    /**
     * @return iterable<array{int, string}>
     */
    public static function provideWordForms(): iterable {
        yield [0, '0 глав'];
        yield [1, '1 глава'];
        yield [2, '2 главы'];
        yield [3, '3 главы'];
        yield [4, '4 главы'];
        yield [5, '5 глав'];
        yield [9, '9 глав'];
        yield [10, '10 глав'];
        yield [11, '11 глав'];
        yield [12, '12 глав'];
        yield [14, '14 глав'];
        yield [19, '19 глав'];
        yield [20, '20 глав'];
        yield [21, '21 глава'];
        yield [22, '22 главы'];
        yield [25, '25 глав'];
        yield [101, '101 глава'];
        yield [110, '110 глав'];
        yield [111, '111 глав'];
        yield [112, '112 глав'];
        yield [121, '121 глава'];
        yield [1004, '1004 главы'];
        yield [-1, '-1 глава'];
        yield [-3, '-3 главы'];
        yield [-11, '-11 глав'];
    }

    #[DataProvider('provideWordForms')]
    public function testWordForm(int $count, string $expected): void {
        self::assertSame($expected, new Formatter()->formatAsWordForm($count, 'глава', 'главы', 'глав'));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideTexts(): iterable {
        yield ['', '<p></p>'];
        yield ['One line', '<p>One line</p>'];
        yield ["One\nTwo", '<p>One</p><p>Two</p>'];
        yield ["One\n\nTwo", '<p>One</p><p>Two</p>'];
        yield ["One\n\n\nTwo", '<p>One</p><p></p><p>Two</p>'];
        yield ["One\n", '<p>One</p><p></p>'];
    }

    #[DataProvider('provideTexts')]
    public function testHtml(string $text, string $expected): void {
        self::assertSame($expected, new Formatter()->formatAsHtml($text));
    }
}
