<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Logger;

use BC\Core\Log\ILogRecorder;
use BC\Core\Log\LogRecorder;
use BC\Logger\AlertingLogger;
use BC\Tests\Fixture\Logger\LogRecorderSpy;
use BC\Tests\Fixture\Logger\TestAlertingLogger;
use BC\Tests\Support\LoggerSpy;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Logger\Enum\LogLevelEnum;
use Runway\Singleton\Container;

#[CoversClass(AlertingLogger::class)]
final class AlertingLoggerTest extends TestCase {
    private LoggerSpy $inner;

    private LogRecorderSpy $recorder;

    private TestAlertingLogger $logger;

    protected function setUp(): void {
        $this->inner = new LoggerSpy();
        $this->recorder = new LogRecorderSpy();
        $this->logger = new TestAlertingLogger($this->inner, $this->recorder);
    }

    public function testLowLevelsAreNotRecorded(): void {
        $this->logger->debug('debug', ['a' => 1]);
        $this->logger->info('info', ['b' => 2]);
        $this->logger->deprecated('deprecated', ['c' => 3]);

        self::assertSame(
            [
                ['level' => 'debug', 'message' => 'debug', 'context' => ['a' => 1]],
                ['level' => 'info', 'message' => 'info', 'context' => ['b' => 2]],
                ['level' => 'deprecated', 'message' => 'deprecated', 'context' => ['c' => 3]],
            ],
            $this->inner->records
        );
        self::assertSame([], $this->recorder->records);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideRecordedLevels(): iterable {
        yield ['warning', 'WARNING'];
        yield ['error', 'ERROR'];
        yield ['critical', 'CRITICAL'];
        yield ['emergency', 'EMERGENCY'];
    }

    #[DataProvider('provideRecordedLevels')]
    public function testHighLevelsAreRecorded(string $method, string $level): void {
        $this->logger->{$method}('Something', ['key' => 'value']);

        self::assertSame([['level' => $method, 'message' => 'Something', 'context' => ['key' => 'value']]], $this->inner->records);
        self::assertSame([['level' => $level, 'message' => 'Something', 'context' => ['key' => 'value']]], $this->recorder->records);
    }

    /**
     * @return iterable<array{mixed, bool}> The HTTP code in the context => whether the record goes to the journal.
     */
    public static function provideHttpCodes(): iterable {
        yield [399, true];
        yield [400, false];
        yield [404, false];
        yield ['422', false];
        yield [499, false];
        yield [500, true];
        yield [0, true];
        yield ['oops', true];
        yield ['45x', true];
        yield ['404 Not Found', false];
    }

    #[DataProvider('provideHttpCodes')]
    public function testClientErrorsAreNotRecorded(mixed $httpCode, bool $isRecorded): void {
        $this->logger->error('API error', [AlertingLogger::CONTEXT_HTTP_CODE => $httpCode]);

        self::assertCount(1, $this->inner->records, 'The file gets it anyway.');
        self::assertCount($isRecorded ? 1 : 0, $this->recorder->records);
    }

    public function testRecordWithoutHttpCode(): void {
        $this->logger->warning('Warning');

        self::assertCount(1, $this->recorder->records);
    }

    public function testRecordingDoesNotLoop(): void {
        $this->recorder->onRecord = function (string $level, string $message): void {
            $this->logger->error("Cannot save: $message");
        };

        $this->logger->error('Original');

        self::assertSame(['Original', 'Cannot save: Original'], $this->inner->getMessages('error'));
        self::assertCount(1, $this->recorder->records);

        // The guard is released after the record.
        $this->recorder->onRecord = null;
        $this->logger->warning('Next');

        self::assertCount(2, $this->recorder->records);
    }

    public function testFailedRecordIsLoggedToTheFile(): void {
        $this->recorder->onRecord = static function (): void {
            throw new LogicException('Database is gone');
        };

        $this->logger->critical('Original');

        self::assertSame(['Original'], $this->inner->getMessages('critical'));
        self::assertSame(
            ['[' . AlertingLogger::class . '::record] Не удалось сохранить запись в журнал: Database is gone'],
            $this->inner->getMessages('error')
        );

        // The guard is released after the failure.
        $this->recorder->onRecord = null;
        $this->logger->warning('Next');

        self::assertCount(2, $this->recorder->records);
    }

    public function testLogLevelIsSetOnTheInnerLogger(): void {
        self::assertSame($this->logger, $this->logger->setLogLevel(LogLevelEnum::ERROR));
        self::assertSame(LogLevelEnum::ERROR, $this->inner->logLevel);
    }

    public function testJournalOfTheContainer(): void {
        $logger = new class(new LoggerSpy()) extends AlertingLogger {
            public function getRecorder(): ILogRecorder {
                return $this->getLogRecorder();
            }
        };

        self::assertInstanceOf(LogRecorder::class, $logger->getRecorder());
        self::assertInstanceOf(
            get_class(Container::getInstance()->getService(ILogRecorder::class)),
            $logger->getRecorder()
        );
    }
}
