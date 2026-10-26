<?php

declare(strict_types=1);

namespace BC\Core\Log;

use BC\Core\Helper\MixedValue;
use BC\Model\LogRecord;
use DateTime;
use DateTimeImmutable;
use Runway\Event\IEventDispatcher;
use Runway\Request\IRequest;
use Runway\Singleton\Container;

class LogRecorder implements ILogRecorder {
    /**
     * Больше в строку таблицы не кладём: огромный контекст (тело запроса,
     * дамп ответа) бесполезен в админке и раздувает таблицу
     */
    private const int MAX_TEXT_BYTES = 60_000;

    private const int MAX_CONTEXT_BYTES = 256 * 1024;

    /**
     * Ключи контекста со стектрейсом исключения: API кладёт массив кадров,
     * обработчик необработанных исключений — готовую строку
     */
    private const array TRACE_KEYS = ['exceptionTrace', 'trace'];

    /**
     * Кадры из этих папок — сам логгер, их в стектрейсе не показываем
     */
    private const array LOGGER_DIRS = [
        PROJECT_ROOT . '/src/Logger/',
        PROJECT_ROOT . '/src/Core/Log/',
        PROJECT_ROOT . '/vendor/bad_cluster/runway/src/Logger/',
    ];

    private bool $isSchemaReady = false;

    public function __construct(
        private readonly IEventDispatcher $eventDispatcher,
        private readonly IRequest $request
    ) {
    }

    public function record(string $level, string $message, array $context): void {
        $this->ensureSchema();

        $now = new DateTime();
        $message = mb_strcut($message, 0, self::MAX_TEXT_BYTES);
        // Числа не различаем: «после 3012 мс» и «после 15001 мс», ID задачи или
        // строки — повторы одной и той же ошибки
        $fingerprint = sha1($level . "\n" . preg_replace('/\d+/', 'N', $message));

        /** @var LogRecord|null $existing */
        $existing = LogRecord::getQueryBuilder()
            ->where('fingerprint = :fingerprint')
            ->setVariable('fingerprint', $fingerprint)
            ->getFirstEntity();

        if ($existing) {
            $existing->setOccurrences($existing->getOccurrences() + 1)
                     ->setLastSeenAt($now)
                     ->persist();

            return;
        }

        $url = $this->getUrl();

        $record = (new LogRecord())
            ->setLevel($level)
            ->setMessage($message)
            ->setTrace(mb_strcut($this->extractTrace($context), 0, self::MAX_TEXT_BYTES))
            ->setContext($this->encodeContext($context))
            ->setUrl(mb_strcut($url, 0, self::MAX_TEXT_BYTES))
            ->setIp($this->request->isCLI() ? '' : $this->request->getIpAddress())
            ->setRequestId($this->request->getRequestId())
            ->setFingerprint($fingerprint)
            ->setOccurrences(1)
            ->setFirstSeenAt($now)
            ->setLastSeenAt($now);

        $record->persist();

        $this->eventDispatcher->dispatch(
            self::EVENT_RECORDED,
            new LogRecordedEnvelope(
                id: $record->getId(),
                level: $level,
                message: $message,
                url: $url,
                recordedAt: DateTimeImmutable::createFromMutable($now)
            )
        );
    }

    /**
     * Миграций в проекте нет, поэтому таблицу создаём сами, как в
     * BackupLogRepository (CREATE TABLE IF NOT EXISTS повторяет schema.sql).
     * Драйвер — через getDataStorageDriver(): в консоли сервис из контейнера
     * может быть ещё не подключён.
     */
    public function ensureSchema(): void {
        if ($this->isSchemaReady) {
            return;
        }

        $driver = Container::getInstance()->getDataStorageDriver();
        $table = $driver->getConnectOptions()->tableNamePrefix . 'log_records';

        $driver->execute(
            'CREATE TABLE IF NOT EXISTS `' . $table . '` ('
            . '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`level` VARCHAR(16) NOT NULL DEFAULT \'\','
            . '`message` TEXT NOT NULL,'
            . '`context` MEDIUMTEXT NOT NULL,'
            . '`trace` TEXT NOT NULL,'
            . '`url` TEXT NOT NULL,'
            . '`ip` VARCHAR(64) NOT NULL DEFAULT \'\','
            . '`request_id` VARCHAR(64) NOT NULL DEFAULT \'\','
            . '`fingerprint` CHAR(40) NOT NULL DEFAULT \'\','
            . '`occurrences` INT UNSIGNED NOT NULL DEFAULT 1,'
            . '`first_seen_at` INT UNSIGNED NOT NULL DEFAULT 0,'
            . '`last_seen_at` INT UNSIGNED NOT NULL DEFAULT 0,'
            . 'PRIMARY KEY (`id`),'
            . 'KEY `idx_fingerprint` (`fingerprint`),'
            . 'KEY `idx_last_seen_at` (`last_seen_at`)'
            . ') ENGINE = InnoDB DEFAULT CHARSET = utf8mb4'
        );

        $this->isSchemaReady = true;
    }

    private function getUrl(): string {
        if ($this->request->isCLI()) {
            $argv = $_SERVER['argv'] ?? [];

            return 'CLI: ' . implode(' ', is_array($argv) ? array_map(MixedValue::toString(...), $argv) : []);
        }

        return $this->request->getMethod() . ' ' . $this->request->getRequestUri();
    }

    /**
     * Стектрейс исключения из контекста (оттуда он убирается, чтобы не
     * дублироваться), а если исключения нет — место, откуда писали в лог
     *
     * @param array<mixed> $context
     */
    private function extractTrace(array &$context): string {
        foreach (self::TRACE_KEYS as $key) {
            if (isset($context[$key])) {
                $trace = $context[$key];
                unset($context[$key]);

                return is_array($trace)
                    ? $this->formatFrames($trace)
                    : MixedValue::toString($trace);
            }
        }

        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        while ($frames && $this->isLoggerFile($frames[0]['file'] ?? '')) {
            array_shift($frames);
        }

        return $this->formatFrames($frames);
    }

    private function isLoggerFile(string $file): bool {
        foreach (self::LOGGER_DIRS as $dir) {
            if (str_starts_with($file, $dir)) {
                return true;
            }
        }

        return false;
    }

    /**
     * В формате Exception::getTraceAsString(), с путями от корня проекта
     *
     * @param array<mixed> $frames
     */
    private function formatFrames(array $frames): string {
        $lines = [];

        foreach (array_values($frames) as $i => $frame) {
            if (!is_array($frame)) {
                continue;
            }

            $place = isset($frame['file'])
                ? str_replace(PROJECT_ROOT . '/', '', MixedValue::toString($frame['file']))
                    . '(' . MixedValue::toString($frame['line'] ?? '?') . ')'
                : '[internal function]';

            $lines[] = sprintf(
                '#%d %s: %s%s%s()',
                $i,
                $place,
                MixedValue::toString($frame['class'] ?? ''),
                MixedValue::toString($frame['type'] ?? ''),
                MixedValue::toString($frame['function'] ?? '')
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<mixed> $context
     */
    private function encodeContext(array $context): string {
        if (!$context) {
            return '';
        }

        $json = (string) json_encode(
            $context,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        return strlen($json) > self::MAX_CONTEXT_BYTES
            ? mb_strcut($json, 0, self::MAX_CONTEXT_BYTES) . "\n…"
            : $json;
    }
}
