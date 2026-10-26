<?php

declare(strict_types=1);

namespace BC\Logger;

use BC\Core\Log\ILogRecorder;
use Runway\Logger\Enum\LogLevelEnum;
use Runway\Logger\ILogger;
use Runway\Singleton\Container;
use Throwable;

/**
 * Предупреждения и ошибки, кроме записи в файл, сохраняет в журнал для админки
 * (ILogRecorder), а тот сообщает о новых записях — модуль Secret шлёт их в Slack.
 *
 * Стоит в цепочке декораторов логгера внутри MaskingLogger (см. порядок в
 * config/services.yaml), поэтому контекст сюда приходит уже замаскированным.
 */
class AlertingLogger implements ILogger {
    /**
     * Ответы API с кодом 4xx — ошибки клиента (кривой запрос, не та форма),
     * а не сайта: в файл пишем, в журнал — нет. Код кладёт в контекст
     * BC\Controller\System\APIExceptionResponseController.
     */
    public const string CONTEXT_HTTP_CODE = 'httpCode';

    /**
     * Сохранение в журнал само может что-то залогировать (упала база, не
     * ответил Slack) — такие записи в журнал уже не идут, иначе зациклимся
     */
    private bool $isRecording = false;

    public function __construct(
        protected readonly ILogger $inner
    ) {
    }

    public function setLogLevel(LogLevelEnum $level): static {
        $this->inner->setLogLevel($level);

        return $this;
    }

    public function debug(string $message, array $context = []): void {
        $this->inner->debug($message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->inner->info($message, $context);
    }

    public function deprecated(string $message, array $context = []): void {
        $this->inner->deprecated($message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->inner->warning($message, $context);
        $this->record('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->inner->error($message, $context);
        $this->record('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void {
        $this->inner->critical($message, $context);
        $this->record('CRITICAL', $message, $context);
    }

    public function emergency(string $message, array $context = []): void {
        $this->inner->emergency($message, $context);
        $this->record('EMERGENCY', $message, $context);
    }

    protected function record(string $level, string $message, array $context): void {
        if ($this->isRecording || $this->isClientError($context)) {
            return;
        }

        $this->isRecording = true;

        try {
            // Не через конструктор: логгер создаётся раньше всех и нужен самой
            // базе и событиям, при внедрении в конструктор получится цикл
            $this->getLogRecorder()->record($level, $message, $context);
        } catch (Throwable $e) {
            // Журнал — дополнение к файлу: запись в файле уже есть, а
            // исключение из логгера уронило бы то, что пыталось залогировать
            $this->inner->error(
                sprintf('[%s] Не удалось сохранить запись в журнал: %s', __METHOD__, $e->getMessage())
            );
        } finally {
            $this->isRecording = false;
        }
    }

    protected function getLogRecorder(): ILogRecorder {
        return Container::getInstance()->getService(ILogRecorder::class);
    }

    protected function isClientError(array $context): bool {
        $code = (int) ($context[self::CONTEXT_HTTP_CODE] ?? 0);

        return $code >= 400 && $code < 500;
    }
}
