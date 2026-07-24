<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\Sftp;

use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;

/**
 * One concrete way to speak SFTP (curl+libssh2, ext-ssh2, …). Which transports actually
 * work depends on how PHP is built on the machine running the backup, so implementations
 * are probed at runtime via isAvailable() and picked by ISftpTransportProvider.
 *
 * Transports are registered with the "backup.sftp_transport" tag; the tag's `priority`
 * orders probing (lower = preferred).
 */
interface ISftpTransport {
    public function getName(): string;

    /** Whether this transport can run in the current PHP environment. */
    public function isAvailable(): bool;

    /** Human-readable reason isAvailable() is false — used in diagnostics. */
    public function getUnavailabilityReason(): string;

    /**
     * Uploads one archive. Must not throw for expected transport failures — return a failed
     * UploadResult instead.
     */
    public function upload(SftpDestinationConfig $destination, UploadTarget $target): UploadResult;
}
