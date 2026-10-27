<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\DatabaseConfig;
use BC\Modules\Backup\Dumper\IDatabaseDumper;
use Throwable;

/**
 * Writes a fake dump (or throws the given exception).
 */
final class FakeDatabaseDumper implements IDatabaseDumper {
    /** @var list<array{DatabaseConfig, string}> */
    public array $dumps = [];

    public function __construct(
        private readonly ?Throwable $exception = null
    ) {}

    public function dump(DatabaseConfig $config, string $outputFile): void {
        $this->dumps[] = [$config, $outputFile];

        if ($this->exception !== null) {
            throw $this->exception;
        }

        file_put_contents($outputFile, '-- dump');
    }
}
