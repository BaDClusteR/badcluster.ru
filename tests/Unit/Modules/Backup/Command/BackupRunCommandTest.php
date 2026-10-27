<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Command;

use BC\Modules\Backup\Command\BackupRunCommand;
use BC\Modules\Backup\Service\BackupResult;
use BC\Modules\Backup\Service\IBackupService;
use BC\Tests\Fixture\Backup\BufferedOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Console\Input\Input;

#[CoversClass(BackupRunCommand::class)]
final class BackupRunCommandTest extends TestCase {
    public function testDescription(): void {
        $command = $this->createCommand(new BackupResult(true, 'a.zip', 0, null, [], []));

        self::assertSame('backup:run', $command->getName());
        self::assertSame(
            'Create a backup (DB + /static), upload it to configured destinations and log the result',
            $command->getDescription()
        );
        self::assertSame([], $command->getParameters());
    }

    public function testSuccess(): void {
        $output = new BufferedOutput();
        $command = $this->createCommand(new BackupResult(
            success: true,
            archiveName: 'bc-backup-20260727-040506.zip',
            sizeBytes: 1536,
            url: 'https://files.example/a.zip',
            succeededDestinations: ['S3', 'SFTP'],
            errors: ['SFTP [weekly]: rotation failed']
        ));

        self::assertSame(0, $command->run(new Input(), $output));
        self::assertSame(
            [
                'info: Starting backup…',
                'line: Archive:      bc-backup-20260727-040506.zip',
                'line: Size:         1.50 KB',
                'line: Uploaded to:  S3, SFTP',
                'line: URL:          https://files.example/a.zip',
                'warning: SFTP [weekly]: rotation failed',
                'success: Backup completed.',
            ],
            $output->lines
        );
    }

    public function testFailure(): void {
        $output = new BufferedOutput();
        $command = $this->createCommand(new BackupResult(false, 'a.zip', 0, null, [], ['mysqldump failed', 'second']));

        self::assertSame(1, $command->run(new Input(), $output));
        self::assertSame(
            [
                'info: Starting backup…',
                'line: Archive:      a.zip',
                'line: Size:         0.00 B',
                'warning: mysqldump failed',
                'warning: second',
                'error: Backup failed.',
            ],
            $output->lines
        );
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function provideSizes(): iterable {
        yield 'bytes' => [1023, '1023.00 B'];
        yield 'one KB' => [1024, '1.00 KB'];
        yield 'MB' => [5 * 1024 * 1024 + 512 * 1024, '5.50 MB'];
        yield 'GB' => [3 * 1024 ** 3, '3.00 GB'];
        yield 'TB' => [2 * 1024 ** 4, '2.00 TB'];
        yield 'beyond TB stays in TB' => [2048 * 1024 ** 4, '2048.00 TB'];
    }

    #[DataProvider('provideSizes')]
    public function testSize(int $bytes, string $expected): void {
        $output = new BufferedOutput();

        $this->createCommand(new BackupResult(true, 'a.zip', $bytes, null, [], []))->run(new Input(), $output);

        self::assertSame("line: Size:         $expected", $output->lines[2]);
    }

    private function createCommand(BackupResult $result): BackupRunCommand {
        return new BackupRunCommand(new readonly class($result) implements IBackupService {
            public function __construct(
                private BackupResult $result
            ) {}

            public function run(): BackupResult {
                return $this->result;
            }
        });
    }
}
