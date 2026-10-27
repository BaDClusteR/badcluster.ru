<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Archiver\IArchiveBuilder;
use Throwable;

/**
 * Writes a fake archive of the given size (or throws the given exception).
 */
final class FakeArchiveBuilder implements IArchiveBuilder {
    /** @var list<array{archivePath: string, dbDumpFile: string, staticDirs: array<string, string>, dumpExists: bool}> */
    public array $builds = [];

    public function __construct(
        private readonly int        $size = 1234,
        private readonly ?Throwable $exception = null
    ) {}

    public function build(string $archivePath, string $dbDumpFile, array $staticDirs): void {
        $this->builds[] = [
            'archivePath' => $archivePath,
            'dbDumpFile'  => $dbDumpFile,
            'staticDirs'  => $staticDirs,
            'dumpExists'  => is_file($dbDumpFile),
        ];

        if ($this->exception !== null) {
            throw $this->exception;
        }

        file_put_contents($archivePath, str_repeat('z', $this->size));
    }
}
