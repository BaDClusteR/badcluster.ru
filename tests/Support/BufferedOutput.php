<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use LogicException;
use Runway\Console\Exception\InvalidAnswerException;
use Runway\Console\Output\Formatter\IOutputFormatter;
use Runway\Console\Output\Formatter\OutputFormatter;
use Runway\Console\Output\IOutput;
use Runway\Console\Output\ProgressBar\IProgressBar;
use Runway\Console\Output\Table\Table;

/**
 * Collects the output of a command (without the formatting) instead of writing it; the prompts get the given answers
 * one by one (a rejected answer is recorded, and the next answer is tried, like the user typing again).
 */
final class BufferedOutput implements IOutput {
    public string $stdout = '';

    public string $stderr = '';

    /** @var list<string> The questions of ask() and secret(). */
    public array $questions = [];

    /** @var list<string> The messages of the rejected answers. */
    public array $rejections = [];

    private readonly IOutputFormatter $formatter;

    /**
     * @param list<string> $answers
     */
    public function __construct(
        private array $answers = []
    ) {
        $this->formatter = new OutputFormatter();
    }

    public function write(string $message): void {
        $this->stdout .= $this->formatter->format(str_replace(PHP_EOL, "\n", $message), false);
    }

    public function writeln(string $message): void {
        $this->write($message . "\n");
    }

    public function success(string $message): void {
        $this->writeln($message);
    }

    public function error(string $message): void {
        $this->writeErrorLine($message);
    }

    public function warning(string $message): void {
        $this->writeErrorLine($message);
    }

    public function writeErrorLine(string $message): void {
        $this->stderr .= $this->formatter->format($message . "\n", false);
    }

    public function info(string $message): void {
        $this->writeln($message);
    }

    public function escape(string $text): string {
        return $this->formatter->escape($text);
    }

    public function table(array $headers, array $rows): void {
        $this->write(new Table($this->formatter)->render($headers, $rows));
    }

    public function createProgressBar(int $max): IProgressBar {
        throw new LogicException('Progress bars are not supported.');
    }

    public function confirm(
        string $question,
        bool   $default = false,
        string $confirmLabel = 'Yes',
        string $cancelLabel = 'No'
    ): bool {
        $this->questions[] = $question;

        return $this->nextAnswer() === $confirmLabel;
    }

    public function choice(string $question, array $choices, int|string|null $default = null): int|string {
        $this->questions[] = $question;
        $answer = $this->nextAnswer();
        $key = array_search($answer, $choices, true);

        return $key === false ? $answer : $key;
    }

    public function ask(string $question, ?callable $validator = null, ?string $default = null): mixed {
        $this->questions[] = $question;

        return $this->validate($validator, $default);
    }

    public function secret(string $question, ?callable $validator = null): mixed {
        $this->questions[] = $question;

        return $this->validate($validator, null);
    }

    public function isInteractive(): bool {
        return true;
    }

    public function getFormatter(): IOutputFormatter {
        return $this->formatter;
    }

    private function validate(?callable $validator, ?string $default): mixed {
        while (true) {
            $answer = $this->nextAnswer();

            if ($answer === '' && $default !== null) {
                $answer = $default;
            }

            if ($validator === null) {
                return $answer;
            }

            try {
                return $validator($answer);
            } catch (InvalidAnswerException $e) {
                $this->rejections[] = $e->getMessage();
            }
        }
    }

    private function nextAnswer(): string {
        if ($this->answers === []) {
            throw new LogicException('No more answers.');
        }

        return array_shift($this->answers);
    }
}
