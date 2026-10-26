<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Logger;

use BC\Core\Log\ILogRecorder;
use Closure;

/**
 * Collects the records of the journal; a callback can act on a record (e.g. throw or log again).
 */
final class LogRecorderSpy implements ILogRecorder {
    /** @var list<array{level: string, message: string, context: array<mixed>}> */
    public array $records = [];

    public int $ensureSchemaCalls = 0;

    /**
     * @param (Closure(string, string, array<mixed>): void)|null $onRecord
     */
    public function __construct(
        public ?Closure $onRecord = null
    ) {}

    /**
     * @param array<mixed> $context
     */
    public function record(string $level, string $message, array $context): void {
        $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];

        if ($this->onRecord !== null) {
            ($this->onRecord)($level, $message, $context);
        }
    }

    public function ensureSchema(): void {
        $this->ensureSchemaCalls++;
    }
}
