<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Model;

use BC\Model\AFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The name of an uploaded file (a material of a game, a song): no paths, no special characters, and no extensions the
 * web server could run or the browser could show as a page of the site.
 */
#[CoversClass(AFile::class)]
final class AFileTest extends TestCase {
    #[DataProvider('provideFilenames')]
    public function testSafeFilename(string $filename, string $expected): void {
        self::assertSame($expected, AFileProbe::safeFilename($filename));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideFilenames(): iterable {
        yield 'plain' => ['Walkthrough (v2).pdf', 'Walkthrough (v2).pdf'];
        yield 'cyrillic' => ['Прохождение игры.zip', 'Прохождение игры.zip'];
        yield 'unix path' => ['../../index.php', 'index.php.txt'];
        yield 'windows path' => ['C:\\Users\\me\\song.mp3', 'song.mp3'];
        yield 'php' => ['shell.php', 'shell.php.txt'];
        yield 'php upper case' => ['shell.PHP5', 'shell.PHP5.txt'];
        yield 'phtml' => ['a.phtml', 'a.phtml.txt'];
        yield 'html' => ['page.html', 'page.html.txt'];
        yield 'svg' => ['logo.svg', 'logo.svg.txt'];
        yield 'double extension is fine' => ['archive.php.zip', 'archive.php.zip'];
        yield 'dot file' => ['.htaccess', 'htaccess'];
        yield 'special characters' => ['a<b>&"c\'.mp3', 'a-b-c-.mp3'];
        yield 'empty' => ['', 'file'];
        yield 'only dots' => ['...', 'file'];
    }
}

abstract class AFileProbe extends AFile {
    public static function safeFilename(string $filename): string {
        return static::buildSafeFilename($filename);
    }
}
