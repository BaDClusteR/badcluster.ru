<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Dumper;

use BC\Modules\Backup\Config\DatabaseConfig;
use BC\Modules\Backup\Exception\BackupException;
use Runway\DataStorage\IDataStorageDriver;
use Runway\Env\Provider\IEnvVariablesProvider;

/**
 * Produces an SQL dump by shelling out to mysqldump / mariadb-dump.
 *
 * Credentials are taken from the framework's DB connection options and handed to the
 * binary through a temporary --defaults-extra-file, so the password never appears in the
 * process list (`ps`).
 */
class MysqldumpDatabaseDumper implements IDatabaseDumper {
    public function __construct(
        private readonly IDataStorageDriver $driver,
        private readonly IEnvVariablesProvider $env,
    ) {
    }

    public function dump(DatabaseConfig $config, string $outputFile): void {
        $options = $this->driver->getConnectOptions();
        $dsn = $this->parseDsn($options->dsn);

        if (empty($dsn['dbname'])) {
            throw new BackupException('Cannot determine database name from DSN: ' . $options->dsn);
        }

        $prefix = $options->tableNamePrefix;
        $database = $dsn['dbname'];
        $charset = $dsn['charset'] ?? 'utf8mb4';

        $defaultsFile = $this->writeDefaultsFile($dsn, $options->user, $options->password, $charset);

        try {
            $command = $this->buildCommand($config, $defaultsFile, $database, $prefix, $charset);
            $this->run($command, $outputFile);
        } finally {
            @unlink($defaultsFile);
        }
    }

    /**
     * @return array{host?: string, port?: string, dbname?: string, charset?: string}
     */
    private function parseDsn(string $dsn): array {
        $withoutScheme = preg_replace('/^\w+:/', '', $dsn) ?? $dsn;
        $result = [];

        foreach (explode(';', $withoutScheme) as $pair) {
            if (str_contains($pair, '=')) {
                [$key, $value] = explode('=', $pair, 2);
                $result[trim($key)] = trim($value);
            }
        }

        return $result;
    }

    private function writeDefaultsFile(array $dsn, ?string $user, ?string $password, string $charset): string {
        $path = tempnam(sys_get_temp_dir(), 'bc-backup-my-');

        if ($path === false) {
            throw new BackupException('Cannot create a temporary mysqldump credentials file.');
        }

        $lines = ['[client]'];
        if (!empty($dsn['host'])) {
            $lines[] = 'host=' . $dsn['host'];
        }
        if (!empty($dsn['port'])) {
            $lines[] = 'port=' . $dsn['port'];
        }
        if ($user !== null && $user !== '') {
            $lines[] = 'user=' . $user;
        }
        if ($password !== null && $password !== '') {
            $lines[] = 'password=' . $password;
        }
        $lines[] = 'default-character-set=' . $charset;

        file_put_contents($path, implode("\n", $lines) . "\n");
        @chmod($path, 0600);

        return $path;
    }

    /**
     * @return string[]
     */
    private function buildCommand(
        DatabaseConfig $config,
        string $defaultsFile,
        string $database,
        string $prefix,
        string $charset,
    ): array {
        $binary = (string) ($this->env->getEnvVariable('BACKUP_MYSQLDUMP_BIN') ?: 'mysqldump');

        $command = [
            $binary,
            '--defaults-extra-file=' . $defaultsFile,
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '--max-allowed-packet=1G',
            '--default-character-set=' . $charset,
        ];

        // The app's PDO DSN connects without TLS, so by default we disable it for the dump too
        // (avoids "SSL connection error" against servers that don't offer SSL). Set BACKUP_DB_SSL=1
        // to keep the client's default SSL behaviour instead. MySQL and MariaDB binaries spell
        // "no TLS" differently, and each rejects the other's option.
        if (!$this->isSslEnabled()) {
            $command[] = $this->isMariaDbBinary($binary) ? '--skip-ssl' : '--ssl-mode=DISABLED';
        }

        foreach ($this->extraArgs() as $arg) {
            $command[] = $arg;
        }

        if ($config->greedy) {
            foreach ($config->excludeTables as $table) {
                $command[] = '--ignore-table=' . $database . '.' . $prefix . $table;
            }

            $command[] = $database;
        } else {
            if ($config->includeTables === []) {
                throw new BackupException(
                    'Database backup is not greedy but the include-table list is empty — nothing would be dumped.'
                );
            }

            $command[] = $database;

            foreach ($config->includeTables as $table) {
                $command[] = $prefix . $table;
            }
        }

        return $command;
    }

    private function isSslEnabled(): bool {
        $value = $this->env->getEnvVariable('BACKUP_DB_SSL');

        return $value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function isMariaDbBinary(string $binary): bool {
        $version = (string) shell_exec(escapeshellarg($binary) . ' --version 2>/dev/null');

        return stripos($version, 'mariadb') !== false;
    }

    /**
     * Extra, verbatim mysqldump arguments from BACKUP_MYSQLDUMP_EXTRA_ARGS (whitespace-separated).
     *
     * @return string[]
     */
    private function extraArgs(): array {
        $raw = trim((string) ($this->env->getEnvVariable('BACKUP_MYSQLDUMP_EXTRA_ARGS') ?? ''));

        if ($raw === '') {
            return [];
        }

        return preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param string[] $command
     */
    private function run(array $command, string $outputFile): void {
        $output = fopen($outputFile, 'wb');

        if ($output === false) {
            throw new BackupException('Cannot open dump output file for writing: ' . $outputFile);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => $output,
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            fclose($output);
            throw new BackupException('Cannot start mysqldump process.');
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        fclose($output);

        if ($exitCode !== 0) {
            throw new BackupException(
                'mysqldump failed (exit code ' . $exitCode . '): ' . trim((string) $stderr)
            );
        }
    }
}
