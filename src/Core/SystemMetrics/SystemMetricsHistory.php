<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemCountersDTO;
use BC\Core\SystemMetrics\DTO\SystemMetricsSampleDTO;

/**
 * Кольцевой буфер точек загрузки в файле. Сэмплы снимаются лениво — на каждый
 * запрос метрик, поэтому история накапливается, пока дашборд хоть у кого-то
 * открыт, и переживает перезагрузку страницы без фоновых воркеров.
 */
class SystemMetricsHistory implements ISystemMetricsHistory {
    /**
     * Дедупликация сэмплов от параллельных вкладок: новая точка добавляется,
     * только если с предыдущей прошло не меньше этого интервала.
     */
    public const int MIN_SAMPLE_INTERVAL_MS = 900;

    public const int MAX_POINTS = 300;

    /**
     * При изменении формата точек/состояния поднять версию — буфер со старым
     * форматом молча сбросится
     */
    private const int STATE_VERSION = 2;

    public function __construct(
        private readonly ISystemMetricsProvider $provider,
        private readonly string $storagePath = PROJECT_ROOT . '/log/system-metrics.json'
    ) {
    }

    public function sample(): SystemMetricsSampleDTO {
        $counters = $this->provider->readCounters();
        $nowMs = (int) round(microtime(true) * 1000);

        $fp = fopen($this->storagePath, 'c+');

        if ($fp === false) {
            return new SystemMetricsSampleDTO([], $counters);
        }

        try {
            flock($fp, LOCK_EX);

            $state = json_decode((string) stream_get_contents($fp), true);

            if (
                !is_array($state)
                || ($state['v'] ?? 0) !== self::STATE_VERSION
                || !is_array($state['points'] ?? null)
            ) {
                $state = ['v' => self::STATE_VERSION, 'last' => null, 'points' => []];
            }

            $state = $this->addPoint($state, $counters, $nowMs);

            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, (string) json_encode($state));
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return new SystemMetricsSampleDTO(
            array_values($state['points']),
            $counters
        );
    }

    private function addPoint(array $state, SystemCountersDTO $counters, int $nowMs): array {
        $last = $state['last'] ?? null;
        $isComparable = is_array($last) && ($last['source'] ?? '') === $counters->source;

        if ($isComparable && $nowMs - (int) ($last['time'] ?? 0) < self::MIN_SAMPLE_INTERVAL_MS) {
            return $state;
        }

        if ($isComparable) {
            $dtSeconds = ($nowMs - (int) ($last['time'] ?? 0)) / 1000;

            $deltaTotal = $counters->cpuTotalTicks - (float) ($last['total'] ?? 0);
            $deltaBusy = $counters->cpuBusyTicks - (float) ($last['busy'] ?? 0);
            $deltaDiskRead = $counters->diskReadOps - (float) ($last['diskR'] ?? 0);
            $deltaDiskWrite = $counters->diskWriteOps - (float) ($last['diskW'] ?? 0);
            $deltaNetRx = $counters->netRxBytes - (float) ($last['netRx'] ?? 0);
            $deltaNetTx = $counters->netTxBytes - (float) ($last['netTx'] ?? 0);

            // Отрицательная дельта — счётчики сбросились (перезагрузка сервера),
            // такую точку пропускаем и просто начинаем отсчёт заново
            $isConsistent = $deltaTotal > 0
                && $deltaBusy >= 0
                && $deltaDiskRead >= 0
                && $deltaDiskWrite >= 0
                && $deltaNetRx >= 0
                && $deltaNetTx >= 0;

            if ($isConsistent) {
                $cpuPercent = min(100.0, max(0.0, 100.0 * $deltaBusy / $deltaTotal));
                $ramPercent = $counters->ramTotalBytes > 0
                    ? min(100.0, max(0.0, 100.0 * $counters->ramUsedBytes / $counters->ramTotalBytes))
                    : 0.0;

                $state['points'][] = [
                    $nowMs,
                    round($cpuPercent, 1),
                    round($ramPercent, 1),
                    round($deltaDiskRead / $dtSeconds, 1),
                    round($deltaDiskWrite / $dtSeconds, 1),
                    round($deltaNetRx / $dtSeconds),
                    round($deltaNetTx / $dtSeconds),
                ];
                $state['points'] = array_slice($state['points'], -self::MAX_POINTS);
            }
        }

        $state['last'] = [
            'time' => $nowMs,
            'busy' => $counters->cpuBusyTicks,
            'total' => $counters->cpuTotalTicks,
            'diskR' => $counters->diskReadOps,
            'diskW' => $counters->diskWriteOps,
            'netRx' => $counters->netRxBytes,
            'netTx' => $counters->netTxBytes,
            'source' => $counters->source,
        ];

        return $state;
    }
}
