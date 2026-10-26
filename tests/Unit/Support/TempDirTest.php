<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Support;

use BC\Tests\Support\TempDir;
use LogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class TempDirTest extends TestCase {
    public function testCreatesAndRemovesFolder(): void {
        $path = TempDir::create();
        mkdir("$path/nested");
        file_put_contents("$path/nested/file.txt", 'x');

        TempDir::remove($path);

        self::assertDirectoryDoesNotExist($path);
    }

    public function testProjectIsNotTemporary(): void {
        self::assertFalse(TempDir::isTemporary(PROJECT_ROOT));
        self::assertFalse(TempDir::isTemporary(PROJECT_ROOT . '/src'));
        self::assertFalse(TempDir::isTemporary(sys_get_temp_dir()));
    }

    public function testRefusesToRemoveProject(): void {
        $this->expectException(LogicException::class);

        TempDir::remove(PROJECT_ROOT);
    }

    public function testRefusesFolderThatLooksLikeProject(): void {
        $path = TempDir::create();
        file_put_contents("$path/composer.json", '{}');

        try {
            self::assertFalse(TempDir::isTemporary($path));
        } finally {
            unlink("$path/composer.json");
            TempDir::remove($path);
        }
    }
}
