<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Core\Extractor;

use BC\Modules\Books\Core\DTO\NoteDTO;
use BC\Modules\Books\Core\Extractor\NotesExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NotesExtractor::class)]
final class NotesExtractorTest extends TestCase {
    public function testNoNotes(): void {
        $result = new NotesExtractor()->extractNotes('<p>Text [1] <sup>not a note</sup></p>');

        self::assertSame('<p>Text [1] <sup>not a note</sup></p>', $result->content);
        self::assertSame([], $result->notes);
    }

    public function testDefaultTemplate(): void {
        $result = new NotesExtractor()->extractNotes('Word<sup>[The note]</sup>.');

        self::assertSame(
            "Word<sup><a href='#n1' id='n1_link' class='note'><span class='note__text'>[1]</span>"
            . "<span class='tooltip note__tooltip'>The note</span></a></sup>.",
            $result->content
        );
        self::assertEquals([new NoteDTO('n1', 'The note')], $result->notes);
    }

    public function testNotesAreNumberedFromStartIndex(): void {
        $result = new NotesExtractor()->extractNotes(
            'A<sup>[first]</sup> B<sup>[second]</sup> C<sup>[]</sup>',
            5,
            '{{{index}}:{{content}}}'
        );

        self::assertSame('A{5:first} B{6:second} C{7:}', $result->content);
        self::assertEquals(
            [new NoteDTO('n5', 'first'), new NoteDTO('n6', 'second'), new NoteDTO('n7', '')],
            $result->notes
        );
    }

    public function testNoteIsNotGreedy(): void {
        $result = new NotesExtractor()->extractNotes('<sup>[a]</sup> and <sup>[b]</sup>', 1, '({{index}})');

        self::assertSame('(1) and (2)', $result->content);
        self::assertSame(['a', 'b'], array_map(static fn(NoteDTO $note): string => $note->content, $result->notes));
    }

    public function testNoteWithMarkupInside(): void {
        $result = new NotesExtractor()->extractNotes('X<sup>[<i>Latin</i> phrase]</sup>', 1, '[{{index}}]');

        self::assertSame('X[1]', $result->content);
        self::assertSame('<i>Latin</i> phrase', $result->notes[0]->content);
    }
}
