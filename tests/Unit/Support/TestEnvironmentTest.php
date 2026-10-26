<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Support;

use BC\Tests\Support\TestEnvironment;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The guards of the tests themselves: the env of the developer is never read, the database is SQLite.
 */
#[CoversClass(TestEnvironment::class)]
final class TestEnvironmentTest extends TestCase {
    public function testEnvLocalIsNotRead(): void {
        $files = array_map(static fn(string $path): string => str_replace('\\', '/', $path), TestEnvironment::getEnvFiles());

        self::assertNotContains(str_replace('\\', '/', PROJECT_ROOT . '/.env.local'), $files);
        self::assertSame(TestEnvironment::ENV_FILE, end($files), 'The test env is the last one: it wins.');
    }

    public function testDatabaseIsSqlite(): void {
        self::assertStringStartsWith('sqlite:', TestEnvironment::getDsn());
    }

    public function testOtherDatabaseIsRefused(): void {
        $this->expectException(LogicException::class);

        TestEnvironment::assertTestDatabase('mysql:host=127.0.0.1;dbname=bc');
    }

    public function testWorkingDirectoryIsTemporary(): void {
        self::assertStringStartsWith(
            str_replace('\\', '/', (string)realpath(sys_get_temp_dir())),
            str_replace('\\', '/', (string)realpath((string)getcwd()))
        );
    }
}
