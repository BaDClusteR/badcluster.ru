<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemCountersDTO;

class ProcSystemMetricsProvider implements ISystemMetricsProvider {
    public const string SOURCE = 'proc';

    /**
     * Только физические диски верхнего уровня: партиции (sda1, nvme0n1p1)
     * и device-mapper (dm-0 при LVM) исключаем, иначе один и тот же IO
     * посчитается дважды.
     */
    private const string DISK_DEVICE_PATTERN = '/^(sd[a-z]+|vd[a-z]+|xvd[a-z]+|nvme\d+n\d+|mmcblk\d+)$/';

    /**
     * Виртуальные интерфейсы (loopback, docker, бриджи, туннели) гоняют
     * локальный трафик, которого «снаружи» нет — не учитываем.
     */
    private const string VIRTUAL_IFACE_PATTERN = '/^(lo$|docker|veth|br-|virbr|tun|tap|wg)/';

    public function isSupported(): bool {
        return is_readable('/proc/stat') && is_readable('/proc/meminfo');
    }

    public function readCounters(): SystemCountersDTO {
        [$busyTicks, $totalTicks, $cpuCores] = $this->readCpu();
        [$ramUsedBytes, $ramTotalBytes] = $this->readRam();
        [$diskReadOps, $diskWriteOps] = $this->readDiskOps();
        [$netRxBytes, $netTxBytes] = $this->readNetBytes();

        return new SystemCountersDTO(
            cpuBusyTicks: $busyTicks,
            cpuTotalTicks: $totalTicks,
            ramUsedBytes: $ramUsedBytes,
            ramTotalBytes: $ramTotalBytes,
            cpuCores: $cpuCores,
            diskReadOps: $diskReadOps,
            diskWriteOps: $diskWriteOps,
            netRxBytes: $netRxBytes,
            netTxBytes: $netTxBytes,
            source: self::SOURCE
        );
    }

    /**
     * @return array{float, float, int}
     */
    private function readCpu(): array {
        $busyTicks = 0.0;
        $totalTicks = 0.0;
        $cpuCores = 0;

        foreach ((file('/proc/stat') ?: []) as $line) {
            if (!preg_match('/^cpu(\d*)\s/', $line, $matches)) {
                continue;
            }

            if ($matches[1] !== '') {
                $cpuCores++;

                continue;
            }

            // cpu  user nice system idle iowait irq softirq steal [guest ...]
            // guest-поля не суммируем: ядро уже включает их в user/nice
            $fields = array_map(
                'floatval',
                array_slice(preg_split('/\s+/', trim($line)) ?: [], 1, 8)
            );

            $totalTicks = array_sum($fields);
            $idleTicks = ($fields[3] ?? 0.0) + ($fields[4] ?? 0.0);
            $busyTicks = $totalTicks - $idleTicks;
        }

        return [$busyTicks, $totalTicks, max(1, $cpuCores)];
    }

    /**
     * @return array{int, int}
     */
    private function readRam(): array {
        $memInfo = (string) file_get_contents('/proc/meminfo');

        $totalBytes = $this->parseMemInfoKb($memInfo, 'MemTotal') * 1024;
        $availableBytes = $this->parseMemInfoKb($memInfo, 'MemAvailable') * 1024;

        return [max(0, $totalBytes - $availableBytes), $totalBytes];
    }

    private function parseMemInfoKb(string $memInfo, string $key): int {
        return preg_match("/^$key:\s+(\d+)\s+kB/m", $memInfo, $matches)
            ? (int) $matches[1]
            : 0;
    }

    /**
     * @return array{float, float}
     */
    private function readDiskOps(): array {
        $readOps = 0.0;
        $writeOps = 0.0;

        // major minor name reads_completed reads_merged sectors_read ms_reading
        // writes_completed ...
        foreach ((file('/proc/diskstats') ?: []) as $line) {
            $fields = preg_split('/\s+/', trim($line)) ?: [];

            if (!preg_match(self::DISK_DEVICE_PATTERN, $fields[2] ?? '')) {
                continue;
            }

            $readOps += (float) ($fields[3] ?? 0);
            $writeOps += (float) ($fields[7] ?? 0);
        }

        return [$readOps, $writeOps];
    }

    /**
     * @return array{float, float}
     */
    private function readNetBytes(): array {
        $rxBytes = 0.0;
        $txBytes = 0.0;

        // Первые две строки — заголовок таблицы
        foreach (array_slice(file('/proc/net/dev') ?: [], 2) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$ifaceName, $counters] = explode(':', $line, 2);

            if (preg_match(self::VIRTUAL_IFACE_PATTERN, trim($ifaceName))) {
                continue;
            }

            // iface: rx_bytes packets errs drop fifo frame compressed multicast tx_bytes ...
            $fields = preg_split('/\s+/', trim($counters)) ?: [];

            $rxBytes += (float) ($fields[0] ?? 0);
            $txBytes += (float) ($fields[8] ?? 0);
        }

        return [$rxBytes, $txBytes];
    }
}
