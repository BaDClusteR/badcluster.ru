<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Widget\Common;

use BC\Widget\Common\Comment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * A comment is stored as HTML (the text of a visitor is escaped when it is saved): it is shown as is.
 */
#[CoversClass(Comment::class)]
final class CommentTest extends TestCase {
    public function testTextIsShownAsIs(): void {
        self::assertSame(
            '<p><b>Админ</b> &amp; Co</p><p>&lt;i&gt;гость&lt;/i&gt;</p>',
            new CommentProbe([])->prepare("<b>Админ</b> &amp; Co\n\n&lt;i&gt;гость&lt;/i&gt;")
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
