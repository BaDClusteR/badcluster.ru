<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Dumper;

use BC\Modules\Backup\Config\DatabaseConfig;
use BC\Modules\Backup\Dumper\MysqldumpDatabaseDumper;
use BC\Modules\Backup\Exception\BackupException;
use BC\Tests\Fixture\Backup\ConnectOptionsDriver;
use BC\Tests\Fixture\Backup\MysqldumpDatabaseDumperSpy;
use BC\Tests\Fixture\Backup\Quietly;
use BC\Tests\Support\EnvStub;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\DataStorage\DTO\DataStorageConnectionOptionsDTO;
use Throwable;

/**
 * mysqldump never runs: MysqldumpDatabaseDumperSpy records the command. Only PHP one-liners are run for real.
 */
#[CoversClass(MysqldumpDatabaseDumper::class)]
final class MysqldumpDatabaseDumperTest extends TestCase {
    use Quietly;

    private const string DSN = 'mysql:host=db.internal;port=3307;dbname=site;charset=utf8mb4';

    private string $dir;

    protected function setUp(): void {
        $this->dir = TempDir::create();
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    public function testGreedyDump(): void {
        $dumper = $this->createDumper();

        $dumper->dump(new DatabaseConfig(true, ['ignored'], ['geoip', 'log_records']), "$this->dir/dump.sql");

        self::assertSame(
            [
                'mysqldump',
                "--defaults-extra-file=$dumper->defaultsFile",
                '--single-transaction',
                '--quick',
                '--no-tablespaces',
                '--max-allowed-packet=1G',
                '--default-character-set=utf8mb4',
                '--ssl-mode=DISABLED',
                '--ignore-table=site.bc_geoip',
                '--ignore-table=site.bc_log_records',
                'site',
            ],
            $dumper->command
        );
        self::assertSame("$this->dir/dump.sql", $dumper->outputFile);
        self::assertSame(['mysqldump'], $dumper->versionChecks);
    }

    public function testSelectiveDump(): void {
        $dumper = $this->createDumper(prefix: '');

        $dumper->dump(new DatabaseConfig(false, ['posts', 'comments'], ['geoip']), "$this->dir/dump.sql");

        self::assertSame(['site', 'posts', 'comments'], array_slice($dumper->command, -3));
        self::assertNotContains('--ignore-table=site.geoip', $dumper->command);
    }

    public function testSelectiveDumpWithoutTables(): void {
        $dumper = $this->createDumper();

        $exception = $this->catch(fn() => $dumper->dump(new DatabaseConfig(false, [], []), "$this->dir/dump.sql"));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame(
            'Database backup is not greedy but the include-table list is empty — nothing would be dumped.',
            $exception->getMessage()
        );
        self::assertSame([], $dumper->command);
    }

    public function testCredentialsFile(): void {
        $dumper = $this->createDumper(user: 'backup', password: 'pa#ss\\word');

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        self::assertSame(
            "[client]\nhost=db.internal\nport=3307\nuser=\"backup\"\npassword=\"pa#ss\\\\word\"\ndefault-character-set=utf8mb4\n",
            $dumper->defaultsFileContent
        );
        self::assertSame(
            str_replace('\\', '/', (string)realpath(sys_get_temp_dir())),
            str_replace('\\', '/', (string)realpath(dirname($dumper->defaultsFile))),
            'The credentials are in the temporary folder of the system.'
        );
        self::assertStringStartsWith('bc-backup-my-', basename($dumper->defaultsFile));
        self::assertFileDoesNotExist($dumper->defaultsFile, 'The credentials are deleted after the dump.');

        if (DIRECTORY_SEPARATOR === '/') {
            self::assertSame(0600, $dumper->defaultsFilePermissions);
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providePasswords(): iterable {
        yield 'plain' => ['secret', 'password="secret"'];
        yield 'comment sign' => ['a#b', 'password="a#b"'];
        yield 'backslash' => ['a\\nb', 'password="a\\\\nb"'];
        yield 'double quote' => ['a"b', "password='a\"b'"];
        yield 'both quotes' => ['a"b\'c', 'password=a"b\'c'];
        yield 'numeric' => ['12345', 'password="12345"'];
    }

    #[DataProvider('providePasswords')]
    public function testPasswordIsQuoted(string $password, string $expectedLine): void {
        $dumper = $this->createDumper(password: $password);

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        self::assertContains($expectedLine, explode("\n", $dumper->defaultsFileContent));
    }

    public function testMinimalCredentialsFile(): void {
        $dumper = $this->createDumper(dsn: 'mysql:dbname=site', user: '', password: null);

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        self::assertSame("[client]\ndefault-character-set=utf8mb4\n", $dumper->defaultsFileContent);
        self::assertContains('--default-character-set=utf8mb4', $dumper->command);
    }

    public function testNoUserAndEmptyPassword(): void {
        $dumper = $this->createDumper(user: null, password: '');

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        self::assertStringNotContainsString('user=', $dumper->defaultsFileContent);
        self::assertStringNotContainsString('password=', $dumper->defaultsFileContent);
    }

    public function testDsnWithSpacesAndCharset(): void {
        $dumper = $this->createDumper(dsn: 'mysql: host = db ; dbname = site ; charset = latin1 ;');

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        self::assertSame("[client]\nhost=db\nuser=\"user\"\npassword=\"password\"\ndefault-character-set=latin1\n", $dumper->defaultsFileContent);
        self::assertContains('--default-character-set=latin1', $dumper->command);
        self::assertSame('site', end($dumper->command));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideDsnsWithoutDatabase(): iterable {
        yield 'no dbname' => ['mysql:host=db'];
        yield 'empty dbname' => ['mysql:host=db;dbname='];
        yield 'not a DSN' => ['mysql'];
    }

    #[DataProvider('provideDsnsWithoutDatabase')]
    public function testDsnWithoutDatabase(string $dsn): void {
        $dumper = $this->createDumper(dsn: $dsn);

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage("Cannot determine database name from DSN: $dsn");

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");
    }

    public function testMariaDbBinary(): void {
        $dumper = $this->createDumper(['BACKUP_MYSQLDUMP_BIN' => '/usr/bin/mariadb-dump'], isMariaDb: true);

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        self::assertSame('/usr/bin/mariadb-dump', $dumper->command[0]);
        self::assertSame(['/usr/bin/mariadb-dump'], $dumper->versionChecks);
        self::assertContains('--skip-ssl', $dumper->command);
        self::assertNotContains('--ssl-mode=DISABLED', $dumper->command);
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function provideSslSettings(): iterable {
        yield 'missing' => [null, false];
        yield 'empty' => ['', false];
        yield 'typed true' => [true, true];
        yield '"1"' => ['1', true];
        yield '"on"' => ['on', true];
        yield '"0"' => ['0', false];
        yield '"off"' => ['off', false];
        yield 'typed false' => [false, false];
    }

    #[DataProvider('provideSslSettings')]
    public function testSsl(mixed $setting, bool $isEnabled): void {
        $dumper = $this->createDumper($setting === null ? [] : ['BACKUP_DB_SSL' => $setting]);

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        if ($isEnabled) {
            self::assertNotContains('--ssl-mode=DISABLED', $dumper->command);
            self::assertSame([], $dumper->versionChecks, 'The binary is not asked for its version.');
        } else {
            self::assertSame('--ssl-mode=DISABLED', $dumper->command[7]);
        }
    }

    public function testExtraArguments(): void {
        $dumper = $this->createDumper(['BACKUP_MYSQLDUMP_EXTRA_ARGS' => "  --hex-blob \t --skip-lock-tables  "]);

        $dumper->dump(new DatabaseConfig(true, [], []), "$this->dir/dump.sql");

        self::assertSame(['--ssl-mode=DISABLED', '--hex-blob', '--skip-lock-tables', 'site'], array_slice($dumper->command, 7));
    }

    public function testBlankExtraArguments(): void {
        $dumper = $this->createDumper(['BACKUP_MYSQLDUMP_EXTRA_ARGS' => '   ']);

        $dumper->dump(new DatabaseConfig(true, [], []), "$this->dir/dump.sql");

        self::assertSame(['--ssl-mode=DISABLED', 'site'], array_slice($dumper->command, 7));
    }

    public function testCredentialsAreDeletedAfterFailedDump(): void {
        $dumper = $this->createDumper();
        $dumper->runException = new BackupException('mysqldump failed');

        $exception = $this->catch(fn() => $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql"));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertNotSame('', $dumper->defaultsFile);
        self::assertFileDoesNotExist($dumper->defaultsFile);
    }

    public function testSelectiveDumpWithPrefix(): void {
        $dumper = $this->createDumper();

        $dumper->dump(new DatabaseConfig(false, ['posts']), "$this->dir/dump.sql");

        self::assertSame(['site', 'bc_posts'], array_slice($dumper->command, -2));
    }

    public function testValueWithEqualsSign(): void {
        $dumper = $this->createDumper(dsn: 'mysql:host=db;dbname=si=te');

        $dumper->dump(new DatabaseConfig(true, [], []), "$this->dir/dump.sql");

        self::assertSame('si=te', end($dumper->command));
    }

    public function testTypedBinary(): void {
        $dumper = $this->createDumper(['BACKUP_MYSQLDUMP_BIN' => 8]);

        $dumper->dump(new DatabaseConfig(), "$this->dir/dump.sql");

        self::assertSame('8', $dumper->command[0]);
    }

    /**
     * The version of a fake binary: a shell script (no Windows).
     */
    public function testMariaDbIsDetectedByVersion(): void {
        if (DIRECTORY_SEPARATOR !== '/') {
            self::markTestSkipped('A shell script stands for the binary.');
        }

        $binary = "$this->dir/mariadb dump";
        file_put_contents(
            $binary,
            "#!/bin/sh\nif [ \"\$1\" = \"--version\" ] && [ \"\$#\" = 1 ]; then echo 'mariadb-dump from 11.4.2-MariaDB'; fi\n"
        );
        chmod($binary, 0700);

        self::assertTrue($this->createDumper()->isMariaDb($binary));
    }

    public function testRunWritesOutput(): void {
        $this->createDumper()->runCommand([PHP_BINARY, '-r', 'echo "-- dump";'], "$this->dir/dump.sql");

        self::assertSame('-- dump', file_get_contents("$this->dir/dump.sql"));
    }

    public function testFailedRun(): void {
        $exception = $this->catch(fn() => $this->createDumper()->runCommand(
            [PHP_BINARY, '-r', 'fwrite(STDERR, " Access denied\n"); exit(2);'],
            "$this->dir/dump.sql"
        ));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('mysqldump failed (exit code 2): Access denied', $exception->getMessage());
    }

    public function testUnwritableOutput(): void {
        $exception = self::quietly(fn(): ?Throwable => $this->catch(fn() => $this->createDumper()->runCommand(
            [PHP_BINARY, '-r', 'echo 1;'],
            "$this->dir/missing/dump.sql"
        )));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame("Cannot open dump output file for writing: $this->dir/missing/dump.sql", $exception->getMessage());
    }

    public function testPhpIsNotMariaDb(): void {
        self::assertFalse($this->createDumper()->isMariaDb(PHP_BINARY));
    }

    /**
     * @param array<string, mixed> $env
     */
    private function createDumper(
        array   $env = [],
        string  $dsn = self::DSN,
        ?string $user = 'user',
        ?string $password = 'password',
        string  $prefix = 'bc_',
        bool    $isMariaDb = false
    ): MysqldumpDatabaseDumperSpy {
        return new MysqldumpDatabaseDumperSpy(
            new ConnectOptionsDriver(new DataStorageConnectionOptionsDTO($dsn, $user, $password, $prefix)),
            new EnvStub($env),
            $isMariaDb
        );
    }

    private function catch(callable $callback): ?Throwable {
        try {
            $callback();
        } catch (Throwable $e) {
            return $e;
        }

        return null;
    }
}
