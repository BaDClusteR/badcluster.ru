<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Shortcode;

use BC\Core\Formatter\Formatter;
use BC\Core\Helper\RussianPlural;
use BC\Core\Shortcode\ShortcodeResolver;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShortcodeResolver::class)]
final class ShortcodeResolverTest extends TestCase {
    private ShortcodeResolver $resolver;

    protected function setUp(): void {
        $this->resolver = new ShortcodeResolver(new Formatter());
    }

    public function testTextWithoutShortcodesIsKept(): void {
        self::assertSame('Просто текст', $this->resolver->expand('Просто текст'));
        self::assertSame('[unknown] и [news]', $this->resolver->expand('[unknown] и [news]'));
        self::assertSame('site_age без скобок', $this->resolver->expand('site_age без скобок'));
        self::assertSame('', $this->resolver->expand(''));
    }

    public function testSiteAge(): void {
        $years = self::getYears('2005-08-02');

        self::assertSame("Сайту {$years}&nbsp;" . self::years($years) . '.', $this->resolver->expand('Сайту [site_age].'));
    }

    public function testRoundedSiteAge(): void {
        $years = self::getYears('2005-08-02');
        $rounded = $years - $years % 5;

        self::assertSame(0, $rounded % 5);
        self::assertGreaterThan($years - 5, $rounded);
        self::assertSame("{$rounded}&nbsp;" . self::years($rounded), $this->resolver->expand('[site_age_round]'));
    }

    public function testMyAge(): void {
        $years = self::getYears('1991-02-01 11:30:00');

        self::assertSame("{$years}&nbsp;" . self::years($years), $this->resolver->expand('[my_age]'));
    }

    public function testSeveralShortcodes(): void {
        $siteAge = self::getYears('2005-08-02');
        $myAge = self::getYears('1991-02-01 11:30:00');

        self::assertSame(
            "$siteAge&nbsp;" . self::years($siteAge) . " / $myAge&nbsp;" . self::years($myAge)
            . " / $siteAge&nbsp;" . self::years($siteAge) . ' [news]',
            $this->resolver->expand('[site_age] / [my_age] / [site_age] [news]')
        );
    }

    public function testUnknownBlock(): void {
        self::assertNull($this->resolver->renderBlock('unknown'));
        self::assertNull($this->resolver->renderBlock('site_age'));
        self::assertNull($this->resolver->renderBlock(''));
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function provideBlockTexts(): iterable {
        yield 'bare' => ['[news]', 'news'];
        yield 'in a paragraph' => ['<p>[news]</p>', 'news'];
        yield 'in markup with spaces' => ['  <b> [news] </b>  ', 'news'];
        yield 'entities around' => ['&#32;[news]&#32;', 'news'];
        yield 'html5 entities around' => ['&Tab;[news]&NewLine;', 'news'];
        yield 'inline shortcode' => ['[site_age]', null];
        yield 'unknown' => ['[weather]', null];
        yield 'uppercase' => ['[NEWS]', null];
        yield 'with text' => ['Новости: [news]', null];
        yield 'two codes' => ['[news][news]', null];
        yield 'no brackets' => ['news', null];
        yield 'empty' => ['', null];
    }

    #[DataProvider('provideBlockTexts')]
    public function testDetectBlockShortcode(string $text, ?string $expected): void {
        self::assertSame($expected, $this->resolver->detectBlockShortcode($text));
    }

    private static function getYears(string $date): int {
        return new DateTime()->diff(new DateTime($date))->y;
    }

    private static function years(int $count): string {
        return RussianPlural::form($count, 'год', 'года', 'лет');
    }
}
