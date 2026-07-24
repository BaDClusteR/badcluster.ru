<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\Sftp;

use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use Throwable;

/**
 * SFTP over the PECL ssh2 extension — the fallback for machines whose libcurl is built
 * without SSH support.
 *
 * Limitation: ext-ssh2 exposes only MD5/SHA1 host fingerprints, so a configured SHA256
 * host-key pin cannot be verified — the transport refuses to upload in that case rather
 * than silently skipping verification.
 */
class Ssh2SftpTransport implements ISftpTransport {
    public function getName(): string {
        return 'ext-ssh2';
    }

    public function isAvailable(): bool {
        return extension_loaded('ssh2');
    }

    public function getUnavailabilityReason(): string {
        return 'the ssh2 PECL extension is not installed';
    }

    public function upload(SftpDestinationConfig $destination, UploadTarget $target): UploadResult {
        if ($destination->hostKeySha256 !== null) {
            return UploadResult::fail(
                'SFTP (ext-ssh2): SHA256 host-key pinning is not supported by this transport; '
                . 'unset BACKUP_SFTP_HOST_KEY_SHA256 or use the curl transport.'
            );
        }

        try {
            $connection = @ssh2_connect($destination->host, $destination->port);
            if (!$connection) {
                return UploadResult::fail(
                    'SFTP (ext-ssh2): cannot connect to ' . $destination->host . ':' . $destination->port
                );
            }

            if (!$this->authenticate($connection, $destination, $error)) {
                return UploadResult::fail('SFTP (ext-ssh2): ' . $error);
            }

            $sftp = @ssh2_sftp($connection);
            if (!$sftp) {
                return UploadResult::fail('SFTP (ext-ssh2): cannot initialise the SFTP subsystem.');
            }

            $remoteDir = $this->resolveBaseDir($sftp, $destination) . '/' . $target->periodFolder;
            $streamRoot = 'ssh2.sftp://' . intval($sftp);

            if (!is_dir($streamRoot . $remoteDir) && !@ssh2_sftp_mkdir($sftp, $remoteDir, 0755, true)) {
                return UploadResult::fail('SFTP (ext-ssh2): cannot create remote directory ' . $remoteDir);
            }

            $remotePath = $remoteDir . '/' . $target->remoteName;

            if (!$this->copyFile($target->localFile, $streamRoot . $remotePath, $error)) {
                return UploadResult::fail('SFTP (ext-ssh2): ' . $error);
            }

            return UploadResult::ok($this->buildPublicUrl($destination, $target));
        } catch (Throwable $e) {
            return UploadResult::fail('SFTP (ext-ssh2) upload failed: ' . $e->getMessage());
        }
    }

    /**
     * @param resource $connection
     */
    private function authenticate($connection, SftpDestinationConfig $destination, ?string &$error): bool {
        if ($destination->keyFilePath !== '') {
            $publicKeyFile = $destination->keyFilePath . '.pub';

            if (!is_readable($publicKeyFile)) {
                $error = 'key auth needs the public key next to the private one (' . $publicKeyFile . ' is missing)';

                return false;
            }

            if (
                !@ssh2_auth_pubkey_file(
                    $connection,
                    $destination->username,
                    $publicKeyFile,
                    $destination->keyFilePath,
                    $destination->keyPassphrase
                )
            ) {
                $error = 'key authentication failed for user ' . $destination->username;

                return false;
            }

            return true;
        }

        if (!@ssh2_auth_password($connection, $destination->username, $destination->password)) {
            $error = 'password authentication failed for user ' . $destination->username;

            return false;
        }

        return true;
    }

    /**
     * @param resource $sftp
     */
    private function resolveBaseDir($sftp, SftpDestinationConfig $destination): string {
        $base = trim($destination->basePath) ?: '~';

        if (str_starts_with($base, '~')) {
            $home = rtrim((string)@ssh2_sftp_realpath($sftp, '.'), '/');

            return $home . (string)substr($base, 1);
        }

        return '/' . trim($base, '/');
    }

    private function copyFile(string $localFile, string $remoteStreamPath, ?string &$error): bool {
        $local = fopen($localFile, 'rb');
        if ($local === false) {
            $error = 'cannot open archive for reading: ' . $localFile;

            return false;
        }

        $remote = @fopen($remoteStreamPath, 'wb');
        if ($remote === false) {
            fclose($local);
            $error = 'cannot open remote file for writing: ' . $remoteStreamPath;

            return false;
        }

        $copied = stream_copy_to_stream($local, $remote);
        fclose($local);
        fclose($remote);

        $expected = filesize($localFile);
        if ($copied === false || $copied !== $expected) {
            $error = 'incomplete upload: ' . var_export($copied, true) . ' of ' . $expected . ' bytes copied';

            return false;
        }

        return true;
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
