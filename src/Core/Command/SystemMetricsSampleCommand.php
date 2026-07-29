<?php

declare(strict_types=1);

namespace BC\Core\Command;

use BC\Core\SystemMetrics\ISystemMetricsHistory;
use Runway\Console\Command\ACommand;
use Runway\Console\Input\IInput;
use Runway\Console\Output\IOutput;
use Runway\Console\Parameter\Enum\ParameterModeEnum;

/**
 * Пополняет кольцевой буфер метрик из крона, чтобы история не прерывалась,
 * когда дашборд никто не смотрит. Пример (посекундная история, без
 * наложения запусков — серия укладывается в минуту):
 *
 *   * * * * * cd /path/to/site && php console metrics:sample -c 55 >/dev/null 2>&1
 */
class SystemMetricsSampleCommand extends ACommand {
    /**
     * Меньше секунды не имеет смысла: дедупликация в буфере всё равно
     * пропускает сэмплы чаще MIN_SAMPLE_INTERVAL_MS
     */
    private const float MIN_INTERVAL_SECONDS = 1.0;

    public function __construct(
        private readonly ISystemMetricsHistory $history
    ) {
        parent::__construct();
    }

    public function getName(): string {
        return 'metrics:sample';
    }

    public function getDescription(): string {
        return 'Sample system metrics into the dashboard ring buffer';
    }

    protected function configure(): void {
        $this
            ->addOption(
                name: 'count',
                shortcut: 'c',
                mode: ParameterModeEnum::VALUE_REQUIRED,
                description: 'How many samples to take',
                default: '1'
            )
            ->addOption(
                name: 'interval',
                shortcut: 'i',
                mode: ParameterModeEnum::VALUE_REQUIRED,
                description: 'Seconds between samples',
                default: '1'
            );
    }

    protected function execute(IInput $input, IOutput $output): int {
        $count = max(1, (int) ($input->getOption('count') ?? 1));
        $intervalSeconds = max(self::MIN_INTERVAL_SECONDS, (float) ($input->getOption('interval') ?? 1));

        $sample = null;

        for ($i = 0; $i < $count; $i++) {
            if ($i > 0) {
                usleep((int) ($intervalSeconds * 1_000_000));
            }

            $sample = $this->history->sample();
        }

        $output->success(sprintf(
            'Took %d sample(s), buffer now holds %d point(s).',
            $count,
            count($sample->points)
        ));

        return 0;
    }
}
