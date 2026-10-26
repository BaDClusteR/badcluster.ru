<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use LogicException;
use PHPUnit\Framework\TestCase;
use Runway\Env\Provider\EnvVariablesProvider;
use Runway\Service\Provider\PathsProvider;

/**
 * The environment of the tests: tests/config/test.env instead of .env.local (the credentials of the database of the
 * developer, the backup destinations), SQLite only, a temporary working directory.
 *
 * A subclass of PathsProvider: the env files are a static list of it, filled by bootstrap.php of the site when the
 * autoloader is loaded, and read once by the first EnvVariablesProvider.
 */
final class TestEnvironment extends PathsProvider {
    public const string ENV_FILE = __DIR__ . '/../config/test.env';

    private static ?string $workingDirectory = null;

    /**
     * @throws LogicException Not in PHPUnit, or the database is not SQLite.
     */
    public static function setUp(): void {
        if (!class_exists(TestCase::class)) {
            throw new LogicException('TestEnvironment is for PHPUnit only.');
        }

        self::replaceEnvFiles(self::ENV_FILE);
        self::assertTestDatabase(self::getDsn());

        // A relative path (e.g. of a mutant of the mutation testing that drops a directory) lands there, not in the
        // project.
        self::$workingDirectory = TempDir::create('bc-tests-cwd-');
        chdir(self::$workingDirectory);

        register_shutdown_function(static function (): void {
            if (self::$workingDirectory !== null) {
                chdir(sys_get_temp_dir());
                TempDir::remove(self::$workingDirectory);
            }
        });
    }

    /**
     * The env files of the packages and .env of the site stay (the defaults), .env.local is replaced by the file.
     */
    public static function replaceEnvFiles(string $envFile): void {
        $local = str_replace('\\', '/', PROJECT_ROOT . '/.env.local');

        static::$envFilePaths = [
            ...array_values(array_filter(
                static::$envFilePaths,
                static fn(string $path): bool => str_replace('\\', '/', $path) !== $local
            )),
            $envFile,
        ];
    }

    /**
     * @return string[]
     */
    public static function getEnvFiles(): array {
        return static::$envFilePaths;
    }

    public static function getDsn(): string {
        $dsn = new EnvVariablesProvider()->getEnvVariable('DSN');

        return is_string($dsn) ? $dsn : '';
    }

    /**
     * @throws LogicException
     */
    public static function assertTestDatabase(string $dsn): void {
        if (!str_starts_with($dsn, 'sqlite:')) {
            throw new LogicException("The tests run on SQLite only, the DSN is \"$dsn\": check tests/config/test.env.");
        }
    }
}
