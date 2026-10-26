<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Comment;

use BC\Core\Comment\CommentSpamFilter;
use BC\Model\Config;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommentSpamFilter::class)]
final class CommentSpamFilterTest extends TestCase {
    protected function setUp(): void {
        SqliteSchema::createModelTables(Config::class);
    }

    public function testNoBlacklist(): void {
        $filter = new CommentSpamFilter();

        self::assertSame([], $filter->getBlacklist());
        self::assertFalse($filter->isSpam('casino', 'casino viagra'));
    }

    public function testEmptyBlacklist(): void {
        Config::setConfig(CommentSpamFilter::CONFIG_NAME, "\n  \n# only a note\n");
        $filter = new CommentSpamFilter();

        self::assertSame([], $filter->getBlacklist());
        self::assertFalse($filter->isSpam('casino', 'text'));
    }

    public function testBlacklistLines(): void {
        Config::setConfig(
            CommentSpamFilter::CONFIG_NAME,
            "casino\r\n  Viagra  \n\n# Заметка для себя\n  #not a note? still a note\rhttp://spam.example\nслово с пробелом\u{2028}last"
        );

        self::assertSame(
            ['casino', 'Viagra', 'http://spam.example', 'слово с пробелом', 'last'],
            new CommentSpamFilter()->getBlacklist()
        );
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function provideComments(): iterable {
        yield 'clean' => ['Вася', 'Отличный пост!', false];
        yield 'word in comment' => ['Вася', 'Best CASINO online', true];
        yield 'word in nickname' => ['http://spam.example/promo', 'Hi', true];
        yield 'russian word in any case' => ['Вася', 'КУПИТЬ ДЁШЕВО', true];
        yield 'substring' => ['Вася', 'viagra-shop', true];
        yield 'phrase' => ['Вася', 'тут слово с пробелом есть', true];
        yield 'phrase split by the nickname' => ['слово с ', 'пробелом', false];
        yield 'uppercase russian word of the blacklist' => ['Вася', 'тут спам-слово', true];
        yield 'note is not a word' => ['Вася', 'my note here', false];
        yield 'similar but not the same' => ['Вася', 'casin0', false];
    }

    #[DataProvider('provideComments')]
    public function testIsSpam(string $nickname, string $comment, bool $isSpam): void {
        Config::setConfig(
            CommentSpamFilter::CONFIG_NAME,
            "casino\nViagra\nhttp://spam.example\nкупить дёшево\nслово с пробелом\n# my note\nСПАМ-СЛОВО"
        );

        self::assertSame($isSpam, new CommentSpamFilter()->isSpam($nickname, $comment));
    }
}
