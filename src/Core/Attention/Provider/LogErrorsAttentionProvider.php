<?php

declare(strict_types=1);

namespace BC\Core\Attention\Provider;

use BC\Core\Attention\DTO\AttentionItemDTO;
use BC\Core\Attention\Enum\AttentionSeverityEnum;
use BC\Core\Attention\IAttentionItemsProvider;
use BC\Core\Helper\RussianPlural;
use BC\Core\Log\ILogRecorder;
use BC\Model\LogRecord;

/**
 * Записи журнала (см. BC\Logger\AlertingLogger), которые ещё не удалили в
 * админке. Пусто — всё спокойно.
 */
readonly class LogErrorsAttentionProvider implements IAttentionItemsProvider {
    public function __construct(
        private ILogRecorder $logRecorder
    ) {
    }

    public function getItems(): array {
        // Пока в журнал ничего не писали, таблицы может и не быть
        $this->logRecorder->ensureSchema();

        $warnings = LogRecord::getQueryBuilder()
            ->where('level = :level')
            ->setVariable('level', LogRecord::LEVEL_WARNING)
            ->count();
        $errors = LogRecord::getQueryBuilder()->count() - $warnings;

        if (!$errors && !$warnings) {
            return [];
        }

        $parts = [];

        if ($errors) {
            $parts[] = $errors . ' ' . RussianPlural::form($errors, 'ошибка', 'ошибки', 'ошибок');
        }

        if ($warnings) {
            $parts[] = $warnings . ' '
                       . RussianPlural::form($warnings, 'предупреждение', 'предупреждения', 'предупреждений');
        }

        return [
            new AttentionItemDTO(
                message: implode(' и ', $parts) . ' в журнале',
                severity: $errors ? AttentionSeverityEnum::Error : AttentionSeverityEnum::Warning,
                adminPath: 'logs'
            ),
        ];
    }
}
