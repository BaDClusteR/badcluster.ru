<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics;

use BC\Core\Helper\MixedValue;
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

            $storedState = json_decode((string) stream_get_contents($fp), true);

            if (
                !is_array($storedState)
                || ($storedState['v'] ?? 0) !== self::STATE_VERSION
                || !is_array($storedState['points'] ?? null)
            ) {
                $state = ['v' => self::STATE_VERSION, 'last' => null, 'points' => []];
            } else {
                $state = [
                    'v' => self::STATE_VERSION,
                    'last' => is_array($storedState['last'] ?? null) ? $storedState['last'] : null,
                    'points' => $this->readPoints($storedState['points']),
                ];
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
            $state['points'],
            $counters
        );
    }

    /**
     * Точки из файла: битые (не массив из 7 чисел) отбрасываются.
     *
     * @param array<mixed> $storedPoints
     *
     * @return list<array{int, float, float, float, float, float, float}>
     */
    private function readPoints(array $storedPoints): array {
        $points = [];

        foreach ($storedPoints as $point) {
            if (!is_array($point) || (count($point) < 7)) {
                continue;
            }

            $point = array_values($point);
            $points[] = [
                MixedValue::toInt($point[0]),
                MixedValue::toFloat($point[1]),
                MixedValue::toFloat($point[2]),
                MixedValue::toFloat($point[3]),
                MixedValue::toFloat($point[4]),
                MixedValue::toFloat($point[5]),
                MixedValue::toFloat($point[6]),
            ];
        }

        return $points;
    }

    /**
     * @param array{v: int, last: array<mixed>|null, points: list<array{int, float, float, float, float, float, float}>} $state
     *
     * @return array{v: int, last: array<mixed>|null, points: list<array{int, float, float, float, float, float, float}>}
     */
    private function addPoint(array $state, SystemCountersDTO $counters, int $nowMs): array {
        $last = $state['last'];
        $isComparable = is_array($last) && ($last['source'] ?? '') === $counters->source;

        if ($isComparable && $nowMs - MixedValue::toInt($last['time'] ?? 0) < self::MIN_SAMPLE_INTERVAL_MS) {
            return $state;
        }

        if ($isComparable) {
            $dtSeconds = ($nowMs - MixedValue::toInt($last['time'] ?? 0)) / 1000;

            $deltaTotal = $counters->cpuTotalTicks - MixedValue::toFloat($last['total'] ?? 0);
            $deltaBusy = $counters->cpuBusyTicks - MixedValue::toFloat($last['busy'] ?? 0);
            $deltaDiskRead = $counters->diskReadOps - MixedValue::toFloat($last['diskR'] ?? 0);
            $deltaDiskWrite = $counters->diskWriteOps - MixedValue::toFloat($last['diskW'] ?? 0);
            $deltaNetRx = $counters->netRxBytes - MixedValue::toFloat($last['netRx'] ?? 0);
            $deltaNetTx = $counters->netTxBytes - MixedValue::toFloat($last['netTx'] ?? 0);

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
