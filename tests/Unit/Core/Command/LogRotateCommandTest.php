<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Command;

use BC\Core\Command\LogRotateCommand;
use BC\Tests\Support\BufferedOutput;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Console\Input\Input;
use Runway\Console\Parameter\Enum\ParameterModeEnum;

/**
 * log:rotate on a temporary log folder (never PROJECT_ROOT/log).
 */
#[CoversClass(LogRotateCommand::class)]
final class LogRotateCommandTest extends TestCase {
    private string $root;

    protected function setUp(): void {
        $this->root = TempDir::create('bc-log-rotate-');
    }

    protected function tearDown(): void {
        TempDir::remove($this->root);
    }

    public function testDefinition(): void {
        $command = new LogRotateCommand($this->root);

        self::assertSame('log:rotate', $command->getName());
        self::assertSame('Compress the daily logs of the past days and delete the old ones', $command->getDescription());

        $parameters = $command->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('keep-days', $parameters[0]->getName());
        self::assertSame(ParameterModeEnum::VALUE_REQUIRED, $parameters[0]->getMode());
        self::assertSame('90', $parameters[0]->getDefault());
    }

    public function testRotation(): void {
        $today = $this->createLog(0, 'today');
        $yesterday = $this->createLog(1, "yesterday\n" . str_repeat('line', 1000));
        $lastKept = $this->createLog(10, 'ten days ago');
        $lastKeptGz = $this->createLog(9, 'nine days ago', '.log.gz');
        $tooOld = $this->createLog(11, 'eleven days ago');
        $tooOldGz = $this->createLog(40, 'forty days ago', '.log.gz');
        $other = dirname($tooOld) . '/notes.txt';
        file_put_contents($other, 'not a log');
        $output = new BufferedOutput();

        self::assertSame(0, new LogRotateCommand($this->root)->run(new Input(options: ['keep-days' => '10']), $output));

        self::assertSame("Compressed: 2, deleted: 2.\n", $output->stdout);
        self::assertSame('', $output->stderr);

        self::assertSame('today', file_get_contents($today), 'Today\'s log is written to: it is not compressed.');
        self::assertFileDoesNotExist($yesterday);
        self::assertSame("yesterday\n" . str_repeat('line', 1000), gzdecode((string)file_get_contents("$yesterday.gz")));
        self::assertFileDoesNotExist("$yesterday.gz.tmp");
        self::assertFileDoesNotExist($lastKept);
        self::assertSame('ten days ago', gzdecode((string)file_get_contents("$lastKept.gz")));
        self::assertSame('nine days ago', file_get_contents($lastKeptGz), 'A compressed log stays as is.');
        self::assertFileDoesNotExist($tooOld);
        self::assertFileDoesNotExist($tooOldGz);
        self::assertFileExists($other);
    }

    public function testEmptyFoldersAreRemoved(): void {
        $old = $this->createLog(400, 'old');
        $kept = $this->createLog(0, 'today');
        mkdir("$this->root/other");
        mkdir("$this->root/2001/13", 0777, true);
        file_put_contents("$this->root/2001/13/readme", 'x');
        $output = new BufferedOutput();

        self::assertSame(0, new LogRotateCommand($this->root)->run(new Input(options: ['keep-days' => '90']), $output));

        self::assertSame("Compressed: 0, deleted: 1.\n", $output->stdout);
        self::assertFileDoesNotExist($old);
        if (date('Y', strtotime('-400 days')) !== date('Y')) {
            self::assertDirectoryDoesNotExist(dirname($old, 2), 'The empty year is removed.');
        }
        self::assertDirectoryDoesNotExist(dirname($old));
        self::assertFileExists($kept);
        self::assertDirectoryExists("$this->root/other", 'Only the folders of the years and the months are removed.');
        self::assertFileExists("$this->root/2001/13/readme");
    }

    public function testDefaultKeepDays(): void {
        $kept = $this->createLog(89, 'kept', '.log.gz');
        $deleted = $this->createLog(91, 'deleted', '.log.gz');
        $output = new BufferedOutput();

        self::assertSame(0, new LogRotateCommand($this->root)->run(new Input(), $output));

        self::assertFileExists($kept);
        self::assertFileDoesNotExist($deleted);
        self::assertSame("Compressed: 0, deleted: 1.\n", $output->stdout);
    }

    public function testAtLeastOneDayIsKept(): void {
        $yesterday = $this->createLog(1, 'yesterday', '.log.gz');
        $older = $this->createLog(2, 'older', '.log.gz');

        self::assertSame(0, new LogRotateCommand($this->root)->run(new Input(options: ['keep-days' => '0']), new BufferedOutput()));

        self::assertFileExists($yesterday);
        self::assertFileDoesNotExist($older);
    }

    public function testNoLogs(): void {
        $output = new BufferedOutput();

        self::assertSame(0, new LogRotateCommand("$this->root/missing")->run(new Input(), $output));

        self::assertSame("Compressed: 0, deleted: 0.\n", $output->stdout);
    }

    private function createLog(int $daysAgo, string $content, string $extension = '.log'): string {
        $time = (int)strtotime("-$daysAgo days");
        $dir = $this->root . '/' . date('Y/m', $time);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = "$dir/" . date('Y-m-d', $time) . $extension;
        file_put_contents($path, $content);

        return $path;
    }
}
