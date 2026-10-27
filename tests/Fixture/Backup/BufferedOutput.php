<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use LogicException;
use Runway\Console\Output\Formatter\IOutputFormatter;
use Runway\Console\Output\Formatter\OutputFormatter;
use Runway\Console\Output\IOutput;
use Runway\Console\Output\ProgressBar\IProgressBar;

/**
 * Collects the output of a command: every line with its kind ("info: Starting…"), without the formatting tags.
 * Prompts, tables and progress bars are not expected.
 */
final class BufferedOutput implements IOutput {
    /** @var list<string> */
    public array $lines = [];

    private readonly IOutputFormatter $formatter;

    public function __construct() {
        $this->formatter = new OutputFormatter();
    }

    public function write(string $message): void {
        $this->add('write', $message);
    }

    public function writeln(string $message): void {
        $this->add('line', $message);
    }

    public function success(string $message): void {
        $this->add('success', $message);
    }

    public function error(string $message): void {
        $this->add('error', $message);
    }

    public function warning(string $message): void {
        $this->add('warning', $message);
    }

    public function writeErrorLine(string $message): void {
        $this->add('stderr', $message);
    }

    public function info(string $message): void {
        $this->add('info', $message);
    }

    public function escape(string $text): string {
        return $this->formatter->escape($text);
    }

    public function table(array $headers, array $rows): void {
        throw new LogicException('Tables are not expected.');
    }

    public function createProgressBar(int $max): IProgressBar {
        throw new LogicException('Progress bars are not expected.');
    }

    public function confirm(
        string $question,
        bool   $default = false,
        string $confirmLabel = 'Yes',
        string $cancelLabel = 'No'
    ): bool {
        throw new LogicException('Prompts are not expected.');
    }

    public function choice(string $question, array $choices, int|string|null $default = null): int|string {
        throw new LogicException('Prompts are not expected.');
    }

    public function ask(string $question, ?callable $validator = null, ?string $default = null): mixed {
        throw new LogicException('Prompts are not expected.');
    }

    public function secret(string $question, ?callable $validator = null): mixed {
        throw new LogicException('Prompts are not expected.');
    }

    public function isInteractive(): bool {
        return false;
    }

    public function getFormatter(): IOutputFormatter {
        return $this->formatter;
    }

    private function add(string $kind, string $message): void {
        $this->lines[] = "$kind: " . $this->formatter->format($message, false);
    }
}
