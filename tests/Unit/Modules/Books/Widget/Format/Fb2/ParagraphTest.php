<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Widget\Format\Fb2;

use BC\Modules\Books\Widget\Format\Fb2\Block\Paragraph;
use BC\Tests\Fixture\Books\Fb2ParagraphProbe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The HTML of the editor becomes the inline markup of FB2: every line is a valid XML fragment.
 */
#[CoversClass(Paragraph::class)]
final class ParagraphTest extends TestCase {
    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideLines')]
    public function testLines(string $html, array $expected): void {
        $lines = new Fb2ParagraphProbe(['text' => $html])->lines();

        self::assertSame($expected, $lines);

        foreach ($lines as $line) {
            self::assertWellFormed("<p xmlns:l=\"http://www.w3.org/1999/xlink\">$line</p>");
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideLines(): iterable {
        yield 'plain text' => ['Просто текст', ['Просто текст']];
        yield 'spaces around are trimmed' => ['  <b>padded</b> text  ', ['<strong>padded</strong> text']];
        yield 'no text' => ['', []];
        yield 'only spaces and breaks' => [" &nbsp; <br> \u{200B} <br>", []];
        yield 'tags are mapped' => [
            '<b>b</b> <strong>s</strong> <i>i</i> <em>e</em> <code>c</code> <kbd>k</kbd> <s>x</s> <del>d</del>'
            . ' <strike>t</strike> <sub>2</sub> <sup>3</sup>',
            [
                '<strong>b</strong> <strong>s</strong> <emphasis>i</emphasis> <emphasis>e</emphasis> <code>c</code>'
                . ' <code>k</code> <strikethrough>x</strikethrough> <strikethrough>d</strikethrough>'
                . ' <strikethrough>t</strikethrough> <sub>2</sub> <sup>3</sup>',
            ],
        ];
        yield 'unknown tags are unwrapped' => [
            '<span class="x">one <u>two</u></span> <mark>three</mark>',
            ['one two three'],
        ];
        yield 'link' => [
            'See <a href=" https://example.com/?a=1&amp;b=&quot;2&quot; ">here</a>',
            ['See <a l:href="https://example.com/?a=1&amp;b=&quot;2&quot;">here</a>'],
        ];
        yield 'link without href is unwrapped' => ['<a>anchor</a> <a href="  ">empty</a>', ['anchor empty']];
        yield 'text is escaped' => [
            'Tom &amp; Jerry &lt;3 "quoted" \'single\'',
            ['Tom &amp; Jerry &lt;3 &quot;quoted&quot; &apos;single&apos;'],
        ];
        yield 'line breaks split the paragraph' => ['one<br>two<br/>three', ['one', 'two', 'three']];
        yield 'leading and trailing breaks are dropped, inner ones are empty lines' => [
            '<br><br>one<br><br>two<br><br>',
            ['one', '', 'two'],
        ];
        yield 'open tags are closed and reopened on a break' => [
            '<b>bold <i>both<br>still both</i> bold</b> plain',
            ['<strong>bold <emphasis>both</emphasis></strong>', '<strong><emphasis>still both</emphasis> bold</strong> plain'],
        ];
        yield 'link is reopened on a break' => [
            '<a href="/x">one<br>two</a>',
            ['<a l:href="/x">one</a>', '<a l:href="/x">two</a>'],
        ];
        yield 'empty pairs left by a break are unwrapped' => [
            '<b>bold<br></b>next',
            ['<strong>bold</strong>', 'next'],
        ];
        yield 'nested empty pairs are unwrapped, the spaces stay' => [
            'a <b><i> </i></b>b',
            ['a  b'],
        ];
        yield 'nbsp becomes a numeric reference' => ["a&nbsp;b", ['a&#160;b']];
        yield 'typography: a one-letter word keeps its neighbour' => ['Он и она', ['Он и&#160;она']];
        yield 'code is not processed by the typographer' => ['<code>и я</code>', ['<code>и я</code>']];
    }

    public function testTag(): void {
        self::assertSame('p', new Fb2ParagraphProbe(['text' => 'x'])->tag());
        self::assertSame('p', new Fb2ParagraphProbe(['text' => 'x', 'alignment' => 'left'])->tag());
        self::assertSame('subtitle', new Fb2ParagraphProbe(['text' => 'x', 'alignment' => 'center'])->tag());
    }

    public function testRender(): void {
        $xml = new Paragraph(['text' => 'one<br><br>two'])->render();

        self::assertSame('<p>one</p><empty-line /><p>two</p>', self::compact($xml));
    }

    public function testRenderSubtitle(): void {
        $xml = new Paragraph(['text' => 'Часть первая', 'alignment' => 'center'])->render();

        self::assertSame('<subtitle>Часть первая</subtitle>', self::compact($xml));
    }

    public function testEmptyParagraphIsEmptyLine(): void {
        self::assertSame('<empty-line />', self::compact(new Paragraph(['text' => '<br>'])->render()));
        self::assertSame('<empty-line />', self::compact(new Paragraph()->render()));
    }

    /**
     * The markup without the indentation between the tags.
     */
    private static function compact(string $xml): string {
        return (string)preg_replace('/>\s+</', '><', trim($xml));
    }

    private static function assertWellFormed(string $xml): void {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            self::assertTrue($document->loadXML($xml), "Not well-formed XML: $xml");
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
