<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Archiver;

use BC\Modules\Backup\Archiver\ZipArchiveBuilder;
use BC\Modules\Backup\Exception\BackupException;
use BC\Tests\Fixture\Backup\Quietly;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;
use ZipArchive;

/**
 * A fake static folder in a temporary folder: never the real one of the site.
 */
#[CoversClass(ZipArchiveBuilder::class)]
final class ZipArchiveBuilderTest extends TestCase {
    use Quietly;

    private string $dir;

    protected function setUp(): void {
        $this->dir = TempDir::create();
        file_put_contents("$this->dir/dump.sql", 'CREATE TABLE t;');

        mkdir("$this->dir/static/photos/2026/07", 0777, true);
        mkdir("$this->dir/static/photos/empty");
        mkdir("$this->dir/static/music");
        file_put_contents("$this->dir/static/photos/2026/07/a.jpg", 'JPEG');
        // Well compressible: deflated, it would be much smaller.
        file_put_contents("$this->dir/static/photos/top.jpg", str_repeat('TOP', 10_000));
        file_put_contents("$this->dir/static/music/track.mp3", 'MP3');
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    public function testArchive(): void {
        $archive = "$this->dir/backup.zip";

        new ZipArchiveBuilder()->build($archive, "$this->dir/dump.sql", [
            'photos' => "$this->dir/static/photos",
            'music'  => "$this->dir/static/music/",
            'absent' => "$this->dir/static/absent",
        ]);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($archive));

        try {
            $entries = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                self::assertIsArray($stat);
                $entries[$stat['name']] = $stat;
            }

            $names = array_keys($entries);
            sort($names);
            self::assertSame(
                [
                    'database.sql',
                    'static/music/',
                    'static/music/track.mp3',
                    'static/photos/',
                    'static/photos/2026/',
                    'static/photos/2026/07/',
                    'static/photos/2026/07/a.jpg',
                    'static/photos/empty/',
                    'static/photos/top.jpg',
                ],
                $names
            );

            self::assertSame('CREATE TABLE t;', $zip->getFromName('database.sql'));
            self::assertSame('JPEG', $zip->getFromName('static/photos/2026/07/a.jpg'));
            self::assertSame('MP3', $zip->getFromName('static/music/track.mp3'));

            foreach (['database.sql', 'static/photos/2026/07/a.jpg', 'static/photos/top.jpg', 'static/music/track.mp3'] as $file) {
                self::assertSame(ZipArchive::CM_STORE, $entries[$file]['comp_method'], "$file is stored without compression.");
                self::assertSame($entries[$file]['size'], $entries[$file]['comp_size']);
            }

            self::assertSame(30_000, $entries['static/photos/top.jpg']['comp_size']);
        } finally {
            $zip->close();
        }
    }

    public function testExistingArchiveIsOverwritten(): void {
        $archive = "$this->dir/backup.zip";
        file_put_contents($archive, 'old');

        new ZipArchiveBuilder()->build($archive, "$this->dir/dump.sql", []);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($archive));
        self::assertSame(1, $zip->numFiles);
        $zip->close();
    }

    public function testMissingDump(): void {
        $exception = self::quietly(fn(): ?Throwable => $this->catch(
            fn() => new ZipArchiveBuilder()->build("$this->dir/backup.zip", "$this->dir/missing.sql", [])
        ));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame("Cannot add $this->dir/missing.sql to the archive.", $exception->getMessage());
    }

    public function testArchiveCannotBeCreated(): void {
        $archive = "$this->dir/missing-folder/backup.zip";

        $exception = self::quietly(fn(): ?Throwable => $this->catch(
            fn() => new ZipArchiveBuilder()->build($archive, "$this->dir/dump.sql", [])
        ));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame("Failed to finalise archive $archive.", $exception->getMessage());
    }

    public function testArchivePathIsFolder(): void {
        $exception = self::quietly(fn(): ?Throwable => $this->catch(
            fn() => new ZipArchiveBuilder()->build("$this->dir/static", "$this->dir/dump.sql", [])
        ));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertMatchesRegularExpression(
            '/^Cannot create archive ' . preg_quote("$this->dir/static", '/') . ' \(code \d+\)\.$/',
            $exception->getMessage()
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
