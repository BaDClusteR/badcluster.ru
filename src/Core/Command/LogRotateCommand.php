<?php

declare(strict_types=1);

namespace BC\Core\Command;

use Runway\Console\Command\ACommand;
use Runway\Console\Input\IInput;
use Runway\Console\Output\IOutput;
use Runway\Console\Parameter\Enum\ParameterModeEnum;

/**
 * Ротация дневных логов (log/ГГГГ/ММ/ГГГГ-ММ-ДД.log): прошедшие дни сжимаются
 * в .gz, совсем старые удаляются вместе с опустевшими папками месяцев и лет.
 * Сегодняшний лог не трогаем — в него ещё пишут, и его же читает дашборд.
 * Пример для крона:
 *
 *   30 4 * * * cd /path/to/site && php console log:rotate >/dev/null 2>&1
 */
class LogRotateCommand extends ACommand {
    private const string DATE_PATTERN = '/^(\d{4}-\d{2}-\d{2})\.log(\.gz)?$/';

    /**
     * @param string $logRoot Папка логов (в тестах — временная)
     */
    public function __construct(
        private readonly string $logRoot = PROJECT_ROOT . '/log'
    ) {
    }

    public function getName(): string {
        return 'log:rotate';
    }

    public function getDescription(): string {
        return 'Compress the daily logs of the past days and delete the old ones';
    }

    protected function configure(): void {
        $this->addOption(
            name: 'keep-days',
            mode: ParameterModeEnum::VALUE_REQUIRED,
            description: 'How many days to keep the logs',
            default: '90'
        );
    }

    protected function execute(IInput $input, IOutput $output): int {
        $keepDays = max(1, (int) ($input->getOption('keep-days') ?? 90));
        $today = date('Y-m-d');
        $oldestKeptTimestamp = strtotime("-$keepDays days");

        if ($oldestKeptTimestamp === false) {
            $output->error("Cannot get the date $keepDays days ago.");

            return 1;
        }

        $oldestKept = date('Y-m-d', $oldestKeptTimestamp);
        $compressed = 0;
        $deleted = 0;

        foreach (glob($this->logRoot . '/[0-9][0-9][0-9][0-9]/[0-9][0-9]/*') ?: [] as $path) {
            if (!preg_match(self::DATE_PATTERN, basename($path), $matches) || $matches[1] === $today) {
                continue;
            }

            if ($matches[1] < $oldestKept) {
                if (!unlink($path)) {
                    $output->error("Cannot delete $path");

                    return 1;
                }

                $deleted++;
            } elseif (!isset($matches[2])) {
                if (!$this->compress($path)) {
                    $output->error("Cannot compress $path");

                    return 1;
                }

                $compressed++;
            }
        }

        $this->removeEmptyDirs();

        $output->success("Compressed: $compressed, deleted: $deleted.");

        return 0;
    }

    /**
     * Пишем во временный файл и переименовываем: прерванный запуск не оставит
     * обрезанный архив вместо лога
     */
    private function compress(string $path): bool {
        $tmpPath = "$path.gz.tmp";
        $source = fopen($path, 'rb');
        $target = gzopen($tmpPath, 'wb9');

        if (!$source || !$target) {
            return false;
        }

        while (!feof($source)) {
            gzwrite($target, (string) fread($source, 1024 * 1024));
        }

        fclose($source);

        return gzclose($target) && rename($tmpPath, "$path.gz") && unlink($path);
    }

    private function removeEmptyDirs(): void {
        // Сначала месяцы, потом годы: год пустеет только без месяцев
        $dirs = [
            ...glob($this->logRoot . '/[0-9][0-9][0-9][0-9]/[0-9][0-9]') ?: [],
            ...glob($this->logRoot . '/[0-9][0-9][0-9][0-9]') ?: [],
        ];

        foreach ($dirs as $dir) {
            // Удаляется только пустая папка, на непустой rmdir просто вернёт false
            @rmdir($dir);
        }
    }
}
