<?php

declare(strict_types=1);

namespace BC\Core\Attention\Provider;

use BC\Core\Attention\DTO\AttentionItemDTO;
use BC\Core\Attention\Enum\AttentionSeverityEnum;
use BC\Core\Attention\IAttentionItemsProvider;
use BC\Core\Helper\RussianPlural;

/**
 * Считает ошибки в сегодняшнем логе. Лог — JSONL по строке на запись,
 * поэтому уровень ищется дешёвой подстрокой без разбора JSON.
 */
class LogErrorsAttentionProvider implements IAttentionItemsProvider {
    private const array ERROR_LEVELS = ['ERROR', 'CRITICAL', 'EMERGENCY'];

    /**
     * Лог больше этого размера не сканируем построчно — сам его размер
     * уже повод для тревоги
     */
    private const int MAX_SCAN_BYTES = 100 * 1024 * 1024;

    private const int MAX_COUNT = 999;

    public function getItems(): array {
        $logPath = $this->getTodayLogPath();

        if (!is_readable($logPath)) {
            return [];
        }

        $sizeBytes = (int) filesize($logPath);

        if ($sizeBytes > self::MAX_SCAN_BYTES) {
            return [
                new AttentionItemDTO(
                    message: sprintf(
                        'Лог за сегодня разросся до %d МБ — что-то очень шумит',
                        (int) round($sizeBytes / 1024 / 1024)
                    ),
                    severity: AttentionSeverityEnum::Error
                ),
            ];
        }

        $count = $this->countErrorLines($logPath);

        if (!$count) {
            return [];
        }

        return [
            new AttentionItemDTO(
                message: sprintf(
                    '%d%s %s в логе за сегодня',
                    $count,
                    $count >= self::MAX_COUNT ? '+' : '',
                    RussianPlural::form($count, 'ошибка', 'ошибки', 'ошибок')
                ),
                severity: AttentionSeverityEnum::Error
            ),
        ];
    }

    private function getTodayLogPath(): string {
        return PROJECT_ROOT . '/log/' . date('Y') . '/' . date('m') . '/' . date('Y-m-d') . '.log';
    }

    private function countErrorLines(string $logPath): int {
        $needles = array_map(
            static fn (string $level): string => '"log_level":"' . $level . '"',
            self::ERROR_LEVELS
        );

        $count = 0;
        $fp = fopen($logPath, 'r');

        if ($fp === false) {
            return 0;
        }

        try {
            while (($line = fgets($fp)) !== false) {
                foreach ($needles as $needle) {
                    if (str_contains($line, $needle)) {
                        $count++;

                        break;
                    }
                }

                if ($count >= self::MAX_COUNT) {
                    break;
                }
            }
        } finally {
            fclose($fp);
        }

        return $count;
    }
}
