<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Widget;

use BC\Modules\Books\Widget\Blocks;
use BC\Modules\Books\Widget\Common\Block\Paragraph;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The paragraphs of the chapters on the site: a line of a dialogue gets its own style.
 */
#[CoversClass(Paragraph::class)]
#[CoversClass(Blocks::class)]
final class ParagraphTest extends TestCase {
    #[DataProvider('provideParagraphs')]
    public function testRender(string $text, string $alignment, string $expected): void {
        self::assertSame($expected, trim(new Paragraph(['text' => $text, 'alignment' => $alignment])->render()));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideParagraphs(): iterable {
        yield 'plain' => ['Текст', '', '<p>Текст</p>'];
        yield 'em dash speech, nbsp after it by the typographer' => ['— Привет, — сказал он.', '', "<p class=\"speech\">—\u{00A0}Привет,\u{00A0}— сказал он.</p>"];
        yield 'speech with an nbsp entity' => ['—&nbsp;Да', '', '<p class="speech">—&nbsp;Да</p>'];
        yield 'en dash speech' => ['– Да', '', '<p class="speech">– Да</p>'];
        yield 'hyphen speech' => ['- Да', '', '<p class="speech">- Да</p>'];
        yield 'leading spaces' => ['  — Да', '', "<p class=\"speech\">  —\u{00A0}Да</p>"];
        yield 'a dash inside is not speech' => ['Он — тут', '', "<p>Он\u{00A0}— тут</p>"];
        yield 'a dash without a space is not speech' => ['—Да', '', '<p>—Да</p>'];
        yield 'speech centered' => ['- Да', 'center', '<p class="speech align-center">- Да</p>'];
        yield 'empty' => ['', '', '<p class="empty" aria-hidden="true">&nbsp;</p>'];
    }

    public function testBlocksUseTheParagraphOfBooks(): void {
        $html = new Blocks(['blocks' => [['type' => 'paragraph', 'data' => ['text' => '- Да']], ['type' => 'delimiter', 'data' => []]]])->render();

        self::assertStringContainsString('<p class="speech">- Да</p>', $html);
        self::assertStringContainsString('delimiter', $html);
    }
}
