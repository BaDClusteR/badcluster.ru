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

        $version = curl_version();
        $protocols = is_array($version)
            ? ($version['protocols'] ?? [])
            : [];

        return is_array($protocols) && in_array('sftp', $protocols, true);
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

        $size = filesize($target->localFile);
        if ($size === false) {
            fclose($handle);

            return UploadResult::fail('Cannot get the size of the archive: ' . $target->localFile);
        }

        $url = 'sftp://' . $destination->host . ':' . $destination->port
            . $this->buildUrlPath($destination, $target);

        $options = [
            CURLOPT_URL                     => $url,
            CURLOPT_UPLOAD                  => true,
            CURLOPT_INFILE                  => $handle,
            CURLOPT_INFILESIZE              => $size,
            CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR,
            CURLOPT_CONNECTTIMEOUT          => 30,
            // No hard time limit — a full backup on a slow link can legitimately take hours.
            // Abort only when the transfer stalls (< 1 KB/s for 60 s).
            CURLOPT_LOW_SPEED_LIMIT         => 1024,
            CURLOPT_LOW_SPEED_TIME          => 60,
        ];

        if ($destination->keyFilePath !== '') {
            $options[CURLOPT_USERNAME] = $destination->username;
            $options[CURLOPT_SSH_AUTH_TYPES] = CURLSSH_AUTH_PUBLICKEY;
            $options[CURLOPT_SSH_PRIVATE_KEYFILE] = $destination->keyFilePath;

            if ($destination->keyPassphrase !== '') {
                $options[CURLOPT_KEYPASSWD] = $destination->keyPassphrase;
            }
        } else {
            $options[CURLOPT_USERPWD] = $destination->username . ':' . $destination->password;
            $options[CURLOPT_SSH_AUTH_TYPES] = CURLSSH_AUTH_PASSWORD;
        }

        if ($destination->hostKeySha256 !== null) {
            $options[CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256] = $destination->hostKeySha256;
        }

        $error = $this->transfer($options);

        fclose($handle);

        if ($error !== null) {
            return UploadResult::fail('SFTP (curl) upload failed: ' . $error);
        }

        return UploadResult::ok($this->buildPublicUrl($destination, $target));
    }

    /**
     * Runs the curl transfer.
     *
     * @param array<int, mixed> $options
     *
     * @return string|null The curl error, null on success.
     */
    protected function transfer(array $options): ?string {
        $curl = curl_init();
        curl_setopt_array($curl, $options);

        return curl_exec($curl) === false ? curl_error($curl) : null;
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
