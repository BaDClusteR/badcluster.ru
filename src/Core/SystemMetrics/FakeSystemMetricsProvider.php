<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemCountersDTO;

/**
 * Заглушка для окружений без /proc (дев на macOS): генерирует правдоподобную
 * «плавающую» нагрузку. Все кумулятивные счётчики — замкнутые формы интегралов
 * от сумм синусоид, поэтому дельта между двумя чтениями даёт гладкую волну
 * без хранения какого-либо состояния.
 */
class FakeSystemMetricsProvider implements ISystemMetricsProvider {
    public const string SOURCE = 'fake';

    private const float TICKS_PER_SECOND = 100.0;
    private const int RAM_TOTAL_BYTES = 8 * 1024 * 1024 * 1024;

    public function readCounters(): SystemCountersDTO {
        $t = microtime(true);

        $ramShare = 0.45 + 0.08 * sin($t / 61) + 0.04 * sin($t / 13);

        return new SystemCountersDTO(
            // мгновенная загрузка: 0.25 + 0.12·sin(t/23) + 0.08·sin(t/7.3) + 0.04·sin(t/2.9)
            cpuBusyTicks: self::TICKS_PER_SECOND * $this->integral($t, 0.25, [
                [0.12, 23],
                [0.08, 7.3],
                [0.04, 2.9],
            ]),
            cpuTotalTicks: self::TICKS_PER_SECOND * $t,
            ramUsedBytes: (int) (self::RAM_TOTAL_BYTES * $ramShare),
            ramTotalBytes: self::RAM_TOTAL_BYTES,
            cpuCores: 8,
            // ~2–60 op/s чтение
            diskReadOps: $this->integral($t, 30, [
                [20, 17],
                [8, 5.1],
            ]),
            // ~10–80 op/s запись
            diskWriteOps: $this->integral($t, 45, [
                [25, 29],
                [10, 3.7],
            ]),
            // ~50–750 KB/s входящий
            netRxBytes: $this->integral($t, 400_000, [
                [250_000, 41],
                [100_000, 9.3],
            ]),
            // ~20–220 KB/s исходящий
            netTxBytes: $this->integral($t, 120_000, [
                [70_000, 37],
                [30_000, 6.7],
            ]),
            uptimeSeconds: 12 * 86_400 + fmod($t, 86_400),
            // Место на диске — реальное даже в заглушке: disk_*_space()
            // переносимы и работают без /proc
            diskUsedBytes: max(0, (int) disk_total_space(PROJECT_ROOT) - (int) disk_free_space(PROJECT_ROOT)),
            diskTotalBytes: (int) disk_total_space(PROJECT_ROOT),
            source: self::SOURCE
        );
    }

    public function isSupported(): bool {
        return true;
    }

    /**
     * ∫(base + Σ amp·sin(t/period)) dt = base·t - Σ amp·period·cos(t/period)
     *
     * @param array<array{float, float}> $waves [амплитуда, период]
     */
    private function integral(float $t, float $base, array $waves): float {
        $result = $base * $t;

        foreach ($waves as [$amplitude, $period]) {
            $result -= $amplitude * $period * cos($t / $period);
        }

        return $result;
    }
}
