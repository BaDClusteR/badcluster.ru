<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Widget\Common;

use BC\Widget\Common\Comment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * A comment is the plain text of a visitor: it is escaped when it is shown.
 */
#[CoversClass(Comment::class)]
final class CommentTest extends TestCase {
    public function testTextIsEscaped(): void {
        self::assertSame(
            '<p>&lt;img src=x onerror=&quot;alert(1)&quot;&gt; &amp; Co</p><p>it&#039;s</p>',
            new CommentProbe([])->prepare("<img src=x onerror=\"alert(1)\"> & Co\nit's")
        );
    }

    public function testEscape(): void {
        self::assertSame('&lt;b&gt;Bob&lt;/b&gt; &quot;Q&quot;', new CommentProbe([])->escapeText('<b>Bob</b> "Q"'));
    }
}

final class CommentProbe extends Comment {
    public function prepare(string $text): string {
        return $this->prepareCommentText($text);
    }

    public function escapeText(string $text): string {
        return $this->escape($text);
    }
}
