<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;

/**
 * Uploads over FTP / FTPS using ext-curl (guaranteed present; ext-ftp is not a project
 * dependency). Missing remote directories are created automatically. The site never
 * deletes remote files — retention is configured on the storage side.
 */
class FtpUploader implements IBackupUploader {
    public function supports(DestinationConfig $destination): bool {
        return $destination instanceof FtpDestinationConfig;
    }

    public function upload(DestinationConfig $destination, UploadTarget $target): UploadResult {
        if (!$destination instanceof FtpDestinationConfig) {
            return UploadResult::fail('FtpUploader received an incompatible destination.');
        }

        $handle = fopen($target->localFile, 'rb');
        if ($handle === false) {
            return UploadResult::fail('Cannot open archive for reading: ' . $target->localFile);
        }

        $remotePath = $this->buildRemotePath($destination, $target);
        $scheme = $destination->ssl ? 'ftps' : 'ftp';
        $url = $scheme . '://' . $destination->host . ':' . $destination->port . '/' . ltrim($remotePath, '/');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL                 => $url,
            CURLOPT_UPLOAD              => true,
            CURLOPT_INFILE              => $handle,
            CURLOPT_INFILESIZE         => filesize($target->localFile),
            CURLOPT_USERPWD             => $destination->username . ':' . $destination->password,
            CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR,
            CURLOPT_USE_SSL             => $destination->ssl ? CURLUSESSL_ALL : CURLUSESSL_NONE,
            CURLOPT_FTPSSLAUTH          => CURLFTPAUTH_DEFAULT,
            CURLOPT_CONNECTTIMEOUT      => 30,
            // No hard time limit — a full backup on a slow link can legitimately take hours.
            // Abort only when the transfer stalls (< 1 KB/s for 60 s).
            CURLOPT_LOW_SPEED_LIMIT     => 1024,
            CURLOPT_LOW_SPEED_TIME      => 60,
        ]);

        // Active mode: tell curl to hand out the port. Passive (default) needs no extra option.
        if (!$destination->passive) {
            curl_setopt($curl, CURLOPT_FTPPORT, '-');
        }

        $ok = curl_exec($curl);
        $error = $ok === false ? curl_error($curl) : null;

        fclose($handle);

        if ($ok === false) {
            return UploadResult::fail('FTP upload failed: ' . $error);
        }

        return UploadResult::ok($this->buildPublicUrl($destination, $target));
    }

    private function buildRemotePath(FtpDestinationConfig $destination, UploadTarget $target): string {
        $base = trim($destination->basePath, '/');
        $segments = array_filter([$base, $target->periodFolder, $target->remoteName]);

        return implode('/', $segments);
    }

    private function buildPublicUrl(FtpDestinationConfig $destination, UploadTarget $target): ?string {
        if ($destination->publicUrlBase === null) {
            return null;
        }

        return rtrim($destination->publicUrlBase, '/')
            . '/' . $target->periodFolder
            . '/' . $target->remoteName;
    }
}