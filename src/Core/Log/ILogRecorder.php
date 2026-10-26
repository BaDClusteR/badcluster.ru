<?php

declare(strict_types=1);

namespace BC\Core\Log;

interface ILogRecorder {
    public const string EVENT_RECORDED = 'log.recorded';

    /**
     * Сохраняет запись лога в журнал (BC\Model\LogRecord). Повтор уже сохранённой
     * записи только увеличивает её счётчик; о новой записи рассылается событие
     * EVENT_RECORDED.
     *
     * @param string $level Уровень, как его пишет логгер: WARNING, ERROR, …
     * @param array<mixed> $context Контекст записи (исключение, стектрейс и т.д.).
     */
    public function record(string $level, string $message, array $context): void;

    /**
     * Создаёт таблицу журнала, если её ещё нет (миграций в проекте нет)
     */
    public function ensureSchema(): void;
}
