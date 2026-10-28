<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Dumper\MysqldumpDatabaseDumper;
use Runway\DataStorage\IDataStorageDriver;
use Runway\Env\Provider\IEnvVariablesProvider;
use Throwable;

/**
 * Records the command (and the credentials file while it exists) instead of running mysqldump.
 */
final class MysqldumpDatabaseDumperSpy extends MysqldumpDatabaseDumper {
    /** @var list<string> */
    public array $command = [];

    public string $outputFile = '';

    public string $defaultsFile = '';

    public string $defaultsFileContent = '';

    public ?int $defaultsFilePermissions = null;

    /** @var list<string> */
    public array $versionChecks = [];

    /** Thrown by the run, like a failed mysqldump. */
    public ?Throwable $runException = null;

    public function __construct(
        IDataStorageDriver    $driver,
        IEnvVariablesProvider $env,
        private readonly bool $isMariaDb = false
    ) {
        parent::__construct($driver, $env);
    }

    /**
     * The real run of a command (e.g. a PHP one-liner standing for mysqldump).
     *
     * @param list<string> $command
     */
    public function runCommand(array $command, string $outputFile): void {
        parent::run($command, $outputFile);
    }

    public function isMariaDb(string $binary): bool {
        return parent::isMariaDbBinary($binary);
    }

    protected function isMariaDbBinary(string $binary): bool {
        $this->versionChecks[] = $binary;

        return $this->isMariaDb;
    }

    /**
     * @param string[] $command
     */
    protected function run(array $command, string $outputFile): void {
        $this->command = array_values($command);
        $this->outputFile = $outputFile;

        foreach ($command as $argument) {
            if (str_starts_with($argument, '--defaults-extra-file=')) {
                $this->defaultsFile = substr($argument, strlen('--defaults-extra-file='));
                $this->defaultsFileContent = (string)file_get_contents($this->defaultsFile);
                $permissions = fileperms($this->defaultsFile);
                $this->defaultsFilePermissions = $permissions === false ? null : $permissions & 0777;
            }
        }

        if ($this->runException !== null) {
            throw $this->runException;
        }
    }
}
