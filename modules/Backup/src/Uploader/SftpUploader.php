<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\Sftp\ISftpTransportProvider;

/**
 * Uploads over SFTP. The actual wire protocol is handled by whichever ISftpTransport is
 * available on this machine (curl+libssh2, ext-ssh2, …) — resolved at runtime, because PHP
 * builds differ between environments.
 *
 * Retention note: the site only ever writes backups. To make them delete-proof, restrict
 * the SFTP user server-side, e.g. in sshd_config:
 *
 *   Match User backup
 *       ForceCommand internal-sftp -P remove,rmdir,rename,posix-rename,setstat,fsetstat
 *       ChrootDirectory /srv/backups
 */
class SftpUploader implements IBackupUploader {
    public function __construct(
        private readonly ISftpTransportProvider $transportProvider,
    ) {}

    public function supports(DestinationConfig $destination): bool {
        return $destination instanceof SftpDestinationConfig;
    }

    public function upload(DestinationConfig $destination, UploadTarget $target): UploadResult {
        if (!$destination instanceof SftpDestinationConfig) {
            return UploadResult::fail('SftpUploader received an incompatible destination.');
        }

        $transport = $this->transportProvider->getTransport();

        if ($transport === null) {
            return UploadResult::fail(
                'No SFTP transport is available in this environment: '
                . implode('; ', $this->transportProvider->getDiagnostics())
            );
        }

        return $transport->upload($destination, $target);
    }
}
