<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Typography;

use BC\Core\Typography\Typographer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Typographer::class)]
final class TypographerTest extends TestCase {
    private const string NBSP = "\u{00A0}";

    /**
     * @return iterable<string, array{string, string}> The HTML => the expected one ("~" is a non-breaking space).
     */
    public static function provideTexts(): iterable {
        // Тире
        yield 'dash between words' => ['Привет — мир', 'Привет~— мир'];
        yield 'several spaces before dash' => ['Привет   — мир', 'Привет~— мир'];
        yield 'hyphen is not a dash' => ['кто-то - что-то', 'кто-то - что-то'];
        yield 'dash without spaces is kept' => ['слово—слово', 'слово—слово'];
        yield 'dialogue at the start of the text' => ['— Да, — сказал он.', '—~Да,~— сказал он.'];
        yield 'dialogue keeps leading whitespace' => ["\n  — Да", "\n  —~Да"];
        yield 'dialogue in a paragraph' => ['<p>— Да</p><p>— Нет</p>', '<p>—~Да</p><p>—~Нет</p>'];
        yield 'dialogue in a paragraph with attributes' => ['<p class="x">— Да</p>', '<p class="x">—~Да</p>'];
        yield 'dialogue in uppercase paragraph' => ['<P>— Да</P>', '<P>—~Да</P>'];
        yield 'dialogue after line break' => ['Он сказал:<br>— Да', 'Он сказал:<br>—~Да'];
        yield 'dialogue after self-closing line break' => ['Он сказал:<br/>— Да', 'Он сказал:<br/>—~Да'];
        yield 'dialogue in list item' => ['<ul><li>— раз</li></ul>', '<ul><li>—~раз</li></ul>'];
        yield 'dialogue in div' => ['x<div>— Да</div>', 'x<div>—~Да</div>'];
        yield 'dialogue in blockquote' => ['x<blockquote>— Да</blockquote>', 'x<blockquote>—~Да</blockquote>'];
        yield 'closing paragraph is not a line start' => ['<p>Текст</p> — продолжение', '<p>Текст</p>~— продолжение'];
        yield 'dash right after inline tag' => ['<i>Ну,</i> — подумал он', '<i>Ну,</i>~— подумал он'];
        yield 'dash after inline tag at line start' => ['<i></i> — Да', '<i></i> —~Да'];
        yield 'empty inline tag keeps line start' => ['<b>&nbsp;</b>— Да', '<b>&nbsp;</b>—~Да'];
        yield 'whitespace keeps line start' => ['<b> </b>— Да', '<b> </b>—~Да'];
        yield 'text ends line start' => ['<b>Он</b>— Да', '<b>Он</b>— Да'];
        yield 'line start dash without space' => ['—Да', '—Да'];

        // Однобуквенные предлоги и союзы
        yield 'one-letter prepositions' => ['в доме и в саду', 'в~доме и~в~саду'];
        yield 'all the one-letter words' => ['а б в и к с о у я', 'а~б в~и~к~с~о~у~я'];
        yield 'uppercase one-letter words' => ['А Б В И К С О У Я', 'А~Б В~И~К~С~О~У~Я'];
        yield 'one-letter word after punctuation' => ['Да, и нет', 'Да, и~нет'];
        yield 'one-letter word after quote' => ['«В доме»', '«В~доме»'];
        yield 'letter at the end of a word' => ['слов дом', 'слов дом'];
        yield 'letter after a digit' => ['5в дом', '5в дом'];
        yield 'letter after a hyphen' => ['что-в дом', 'что-в дом'];
        yield 'several spaces after preposition' => ['в   доме', 'в~доме'];
        yield 'preposition at the end' => ['пришёл в ', 'пришёл в '];
        yield 'preposition before a tag' => ['в <b>доме</b>', 'в <b>доме</b>'];
        yield 'latin letter is not a russian preposition' => ['c доме', 'c доме'];

        // Английские вставки
        yield 'english articles' => ['a dog, an apple, the end', 'a~dog, an~apple, the~end'];
        yield 'english prepositions' => ['Call of Duty', 'Call of~Duty'];
        yield 'english short words' => [
            'at home by me in it of us on it to go for me as is and so or not but no nor yes',
            'at~home by~me in~it of~us on~it to~go for~me as~is and~so or~not but~no nor~yes',
        ];
        yield 'english words in any case' => ['The End OF Days', 'The~End OF~Days'];
        yield 'english pronoun' => ['I am', 'I~am'];
        yield 'lowercase english pronoun' => ['i am', 'i am'];
        yield 'english word inside a word' => ['bandit thor forum another day', 'bandit thor forum another day'];
        yield 'english word after an apostrophe' => ["l'a b, d’or x", "l'a b, d’or x"];
        yield 'english word after a hyphen' => ['e-of x', 'e-of x'];
        yield 'english word after a digit' => ['1a b', '1a b'];
        yield 'two in the chest' => ['two in the chest', 'two in~the~chest'];

        // Код не трогаем
        yield 'code' => ['<code>в доме — да</code>', '<code>в доме — да</code>'];
        yield 'kbd' => ['<kbd>в доме</kbd>', '<kbd>в доме</kbd>'];
        yield 'pre' => ['<pre>в доме</pre>', '<pre>в доме</pre>'];
        yield 'uppercase code' => ['<CODE>в доме</CODE>', '<CODE>в доме</CODE>'];
        yield 'text after code' => ['<code>в доме</code> и в саду', '<code>в доме</code> и~в~саду'];
        yield 'nested raw tags' => [
            '<pre><code>в доме</code> в саду</pre> в доме',
            '<pre><code>в доме</code> в саду</pre> в~доме',
        ];
        yield 'unbalanced closing raw tag' => ['</code>в доме', '</code>в~доме'];
        yield 'two unbalanced closing raw tags then code' => [
            '</code></code><code>в доме</code> в доме',
            '</code></code><code>в доме</code> в~доме',
        ];
        yield 'attributes are not text' => ['<a title="в доме — да">в доме</a>', '<a title="в доме — да">в~доме</a>'];

        yield 'empty' => ['', ''];
    }

    #[DataProvider('provideTexts')]
    public function testProcess(string $html, string $expected): void {
        self::assertSame(
            str_replace('~', self::NBSP, $expected),
            new Typographer()->process($html)
        );
    }

    public function testInvalidUtf8IsReturnedAsIs(): void {
        $html = "в доме \xff — да";

        self::assertSame($html, new Typographer()->process($html));
    }
}
