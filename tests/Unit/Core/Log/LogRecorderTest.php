<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Log;

use BC\Core\Log\ILogRecorder;
use BC\Core\Log\LogRecordedEnvelope;
use BC\Core\Log\LogRecorder;
use BC\Model\LogRecord;
use BC\Tests\Fixture\Logger\SqliteLogRecorder;
use BC\Tests\Support\EventDispatcherSpy;
use BC\Tests\Support\SqliteSchema;
use BC\Tests\Support\TestRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogRecorder::class)]
#[CoversClass(LogRecordedEnvelope::class)]
final class LogRecorderTest extends TestCase {
    private EventDispatcherSpy $events;

    private TestRequest $request;

    private SqliteLogRecorder $recorder;

    protected function setUp(): void {
        SqliteSchema::createModelTables(LogRecord::class);

        $this->events = new EventDispatcherSpy();
        $this->request = TestRequest::create('POST', '/admin/api/fact')
                                    ->setRequestUri('/admin/api/fact?x=1')
                                    ->setIpAddress('198.51.100.7')
                                    ->setRequestId('req-1');
        $this->recorder = new SqliteLogRecorder($this->events, $this->request);
    }

    public function testNewRecord(): void {
        $before = time();
        $this->recorder->record('ERROR', 'Cannot save fact 42', ['id' => 42, 'name' => 'Факт / 1']);

        $records = LogRecord::find();
        self::assertCount(1, $records);
        $record = $records[0];

        self::assertSame('ERROR', $record->getLevel());
        self::assertSame('Cannot save fact 42', $record->getMessage());
        self::assertSame("{\n    \"id\": 42,\n    \"name\": \"Факт / 1\"\n}", $record->getContext());
        self::assertSame('POST /admin/api/fact?x=1', $record->getUrl());
        self::assertSame('198.51.100.7', $record->getIp());
        self::assertSame('req-1', $record->getRequestId());
        self::assertSame(sha1("ERROR\nCannot save fact N"), $record->getFingerprint());
        self::assertSame(1, $record->getOccurrences());
        self::assertGreaterThanOrEqual($before, $record->getFirstSeenAt()->getTimestamp());
        self::assertSame($record->getFirstSeenAt()->getTimestamp(), $record->getLastSeenAt()->getTimestamp());
        self::assertSame(1, $this->recorder->ensureSchemaCalls);

        self::assertCount(1, $this->events->events);
        self::assertSame(ILogRecorder::EVENT_RECORDED, $this->events->events[0]['name']);
        $envelope = $this->events->events[0]['envelope'];
        self::assertInstanceOf(LogRecordedEnvelope::class, $envelope);
        self::assertSame($record->getId(), $envelope->id);
        self::assertSame('ERROR', $envelope->level);
        self::assertSame('Cannot save fact 42', $envelope->message);
        self::assertSame('POST /admin/api/fact?x=1', $envelope->url);
        self::assertSame($record->getFirstSeenAt()->getTimestamp(), $envelope->recordedAt->getTimestamp());
    }

    public function testRepeatOnlyCountsOccurrences(): void {
        $this->recorder->record('ERROR', 'Timeout after 3012 ms', []);
        $first = LogRecord::find()[0];
        $this->recorder->record('ERROR', 'Timeout after 15001 ms', ['other' => 'context']);
        $this->recorder->record('ERROR', 'Timeout after 7 ms', []);

        $records = LogRecord::find();
        self::assertCount(1, $records);
        self::assertSame(3, $records[0]->getOccurrences());
        self::assertSame('Timeout after 3012 ms', $records[0]->getMessage(), 'The first message stays.');
        self::assertSame('', $records[0]->getContext());
        self::assertSame($first->getFirstSeenAt()->getTimestamp(), $records[0]->getFirstSeenAt()->getTimestamp());
        self::assertCount(1, $this->events->events, 'A repeat is not a new record.');
        self::assertSame(3, $this->recorder->ensureSchemaCalls);
    }

    public function testOtherLevelIsOtherRecord(): void {
        $this->recorder->record('ERROR', 'Same message', []);
        $this->recorder->record('WARNING', 'Same message', []);
        $this->recorder->record('ERROR', 'Other message', []);

        self::assertCount(3, LogRecord::find());
        self::assertCount(3, $this->events->events);
    }

    public function testCliRecord(): void {
        $this->request->isCli = true;
        $argv = $_SERVER['argv'] ?? null;
        $_SERVER['argv'] = ['console', 'backup:run', '--force'];

        try {
            $this->recorder->record('WARNING', 'Message', []);
        } finally {
            $_SERVER['argv'] = $argv;
        }

        $record = LogRecord::find()[0];
        self::assertSame('CLI: console backup:run --force', $record->getUrl());
        self::assertSame('', $record->getIp());
    }

    public function testCliRecordWithoutArguments(): void {
        $this->request->isCli = true;
        $argv = $_SERVER['argv'] ?? null;
        unset($_SERVER['argv']);

        try {
            $this->recorder->record('WARNING', 'Message', []);
        } finally {
            $_SERVER['argv'] = $argv;
        }

        self::assertSame('CLI: ', LogRecord::find()[0]->getUrl());
    }

    public function testExceptionTraceFramesAreFormatted(): void {
        $this->recorder->record('ERROR', 'Exception', [
            'exceptionType'  => 'LogicException',
            'exceptionTrace' => [
                ['file' => PROJECT_ROOT . '/src/Api/Endpoint/Facts.php', 'line' => 12, 'class' => 'BC\Api\Endpoint\Facts', 'type' => '->', 'function' => 'getList'],
                ['function' => 'array_map'],
                'not a frame',
                ['file' => '/elsewhere/file.php', 'function' => 'helper'],
            ],
        ]);

        $record = LogRecord::find()[0];
        self::assertSame(
            "#0 src/Api/Endpoint/Facts.php(12): BC\Api\Endpoint\Facts->getList()\n"
            . "#1 [internal function]: array_map()\n"
            . "#3 /elsewhere/file.php(?): helper()",
            $record->getTrace()
        );
        self::assertSame("{\n    \"exceptionType\": \"LogicException\"\n}", $record->getContext(), 'The trace is not repeated in the context.');
    }

    public function testTraceStringIsKept(): void {
        $this->recorder->record('ERROR', 'Uncaught', ['trace' => "#0 {main}", 'a' => 1, 'exceptionTrace' => 'ignored']);

        $record = LogRecord::find()[0];
        self::assertSame('ignored', $record->getTrace(), 'exceptionTrace goes first.');
        self::assertSame("{\n    \"trace\": \"#0 {main}\",\n    \"a\": 1\n}", $record->getContext());
    }

    public function testPlaceOfTheCallIsTheTraceWithoutTheLoggerFrames(): void {
        $this->recorder->record('ERROR', 'No exception', []);

        $trace = LogRecord::find()[0]->getTrace();

        self::assertStringStartsWith('#0 tests/Unit/Core/Log/LogRecorderTest.php(', $trace);
        self::assertStringContainsString('): BC\Core\Log\LogRecorder->record()', strtok($trace, "\n") ?: '');
        self::assertStringNotContainsString('src/Core/Log/', $trace);
        self::assertStringNotContainsString(PROJECT_ROOT, $trace);
    }

    public function testLongTextsAreCut(): void {
        $message = str_repeat('ж', 40_000);
        $this->recorder->record('ERROR', $message, ['data' => str_repeat('x', 300 * 1024)]);

        $record = LogRecord::find()[0];
        self::assertSame(60_000, strlen($record->getMessage()));
        self::assertSame(str_repeat('ж', 30_000), $record->getMessage());
        self::assertSame(256 * 1024 + strlen("\n…"), strlen($record->getContext()));
        self::assertStringEndsWith("xxx\n…", $record->getContext());

        self::assertInstanceOf(LogRecordedEnvelope::class, $this->events->events[0]['envelope']);
        self::assertSame($record->getMessage(), $this->events->events[0]['envelope']->message);
    }

    public function testTextsAreCutBetweenCharacters(): void {
        // One byte and two-byte characters: byte 60 000 is in the middle of a character.
        $text = 'a' . str_repeat('ж', 40_000);
        $this->request->setRequestUri('/' . str_repeat('ж', 40_000));

        $this->recorder->record('ERROR', $text, ['trace' => $text, 'data' => 'bc' . str_repeat('ж', 200_000)]);

        $record = LogRecord::find()[0];
        self::assertSame('a' . str_repeat('ж', 29_999), $record->getMessage());
        self::assertSame('a' . str_repeat('ж', 29_999), $record->getTrace());
        self::assertTrue('POST /' . str_repeat('ж', 29_997) === $record->getUrl(), 'The URL is cut.');
        self::assertTrue(mb_check_encoding($record->getContext(), 'UTF-8'));
        self::assertStringStartsWith("{\n    \"data\": \"bcж", $record->getContext());
        self::assertSame(256 * 1024 - 1 + strlen("\n…"), strlen($record->getContext()));
    }

    public function testScalarTraceIsText(): void {
        $this->recorder->record('ERROR', 'Message', ['trace' => 12345]);

        self::assertSame('12345', LogRecord::find()[0]->getTrace());
    }

    public function testFramesAreNumberedInOrder(): void {
        $this->recorder->record('ERROR', 'Message', ['exceptionTrace' => [5 => ['function' => 'a'], 9 => ['function' => 'b']]]);

        self::assertSame("#0 [internal function]: a()\n#1 [internal function]: b()", LogRecord::find()[0]->getTrace());
    }

    public function testContextJustUnderTheLimitIsNotCut(): void {
        // {"data":"…"} pretty-printed: 4 + 4 + 4 + 4 + 2 = 18 bytes around the value.
        $value = str_repeat('x', 256 * 1024 - 18);
        $this->recorder->record('ERROR', 'Message', ['data' => $value]);

        $context = LogRecord::find()[0]->getContext();
        self::assertSame(256 * 1024, strlen($context));
        self::assertStringEndsWith("x\"\n}", $context);
    }

    public function testInvalidUtf8InContext(): void {
        $this->recorder->record('ERROR', 'Message', ['data' => "a\xffb"]);

        self::assertSame("{\n    \"data\": \"a\u{FFFD}b\"\n}", LogRecord::find()[0]->getContext());
    }
}
