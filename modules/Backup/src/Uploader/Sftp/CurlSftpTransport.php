<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\Sftp;

use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;

/**
 * SFTP over ext-curl. Requires libcurl built with an SSH library (libssh2/libssh) — true on
 * most distro builds, but not all, hence the runtime probe.
 *
 * Supports password and private-key auth, and an optional SHA256 host-key pin.
 */
class CurlSftpTransport implements ISftpTransport {
    public function getName(): string {
        return 'curl';
    }

    public function isAvailable(): bool {
        if (!function_exists('curl_version')) {
            return false;
        }

        return in_array('sftp', (curl_version()['protocols'] ?? []), true);
    }

    public function getUnavailabilityReason(): string {
        if (!function_exists('curl_version')) {
            return 'ext-curl is not loaded';
        }

        return 'libcurl is built without SFTP support (no SSH library)';
    }

    public function upload(SftpDestinationConfig $destination, UploadTarget $target): UploadResult {
        $handle = fopen($target->localFile, 'rb');
        if ($handle === false) {
            return UploadResult::fail('Cannot open archive for reading: ' . $target->localFile);
        }

        $url = 'sftp://' . $destination->host . ':' . $destination->port
            . $this->buildUrlPath($destination, $target);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL                     => $url,
            CURLOPT_UPLOAD                  => true,
            CURLOPT_INFILE                  => $handle,
            CURLOPT_INFILESIZE              => filesize($target->localFile),
            CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR,
            CURLOPT_CONNECTTIMEOUT          => 30,
            // No hard time limit — a full backup on a slow link can legitimately take hours.
            // Abort only when the transfer stalls (< 1 KB/s for 60 s).
            CURLOPT_LOW_SPEED_LIMIT         => 1024,
            CURLOPT_LOW_SPEED_TIME          => 60,
        ]);

        if ($destination->keyFilePath !== '') {
            curl_setopt($curl, CURLOPT_USERNAME, $destination->username);
            curl_setopt($curl, CURLOPT_SSH_AUTH_TYPES, CURLSSH_AUTH_PUBLICKEY);
            curl_setopt($curl, CURLOPT_SSH_PRIVATE_KEYFILE, $destination->keyFilePath);

            if ($destination->keyPassphrase !== '') {
                curl_setopt($curl, CURLOPT_KEYPASSWD, $destination->keyPassphrase);
            }
        } else {
            curl_setopt($curl, CURLOPT_USERPWD, $destination->username . ':' . $destination->password);
            curl_setopt($curl, CURLOPT_SSH_AUTH_TYPES, CURLSSH_AUTH_PASSWORD);
        }

        if ($destination->hostKeySha256 !== null) {
            curl_setopt($curl, CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256, $destination->hostKeySha256);
        }

        $ok = curl_exec($curl);
        $error = $ok === false ? curl_error($curl) : null;

        fclose($handle);

        if ($ok === false) {
            return UploadResult::fail('SFTP (curl) upload failed: ' . $error);
        }

        return UploadResult::ok($this->buildPublicUrl($destination, $target));
    }

    /**
     * In curl SFTP URLs the path after the host is absolute; home-relative paths are
     * written as "/~/dir". A basePath of "~" or "~/dir" maps to the latter.
     */
    private function buildUrlPath(SftpDestinationConfig $destination, UploadTarget $target): string {
        $base = trim($destination->basePath) ?: '~';

        $prefix = str_starts_with($base, '~')
            ? '/' . rtrim($base, '/')
            : '/' . trim($base, '/');

        return $prefix . '/' . $target->periodFolder . '/' . $target->remoteName;
    }

    private function buildPublicUrl(SftpDestinationConfig $destination, UploadTarget $target): ?string {
        if ($destination->publicUrlBase === null) {
            return null;
        }

        return rtrim($destination->publicUrlBase, '/')
            . '/' . $target->periodFolder
            . '/' . $target->remoteName;
    }
}
