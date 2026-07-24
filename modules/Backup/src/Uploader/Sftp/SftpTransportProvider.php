<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\Sftp;

use Runway\Singleton\Container;

/**
 * Probes all services tagged "backup.sftp_transport" (in tag-priority order, lower first)
 * and hands out the first one that can run in the current PHP environment.
 */
class SftpTransportProvider implements ISftpTransportProvider {
    public function getTransport(): ?ISftpTransport {
        foreach ($this->getTransports() as $transport) {
            if ($transport->isAvailable()) {
                return $transport;
            }
        }

        return null;
    }

    public function getDiagnostics(): array {
        $lines = [];

        foreach ($this->getTransports() as $transport) {
            $lines[] = $transport->isAvailable()
                ? $transport->getName() . ': available'
                : $transport->getName() . ': unavailable (' . $transport->getUnavailabilityReason() . ')';
        }

        return $lines ?: ['no SFTP transports registered'];
    }

    /**
     * @return ISftpTransport[]
     */
    private function getTransports(): array {
        return array_values(
            array_filter(
                Container::getInstance()->getServicesByTag('backup.sftp_transport'),
                static fn($service): bool => $service instanceof ISftpTransport
            )
        );
    }
}
