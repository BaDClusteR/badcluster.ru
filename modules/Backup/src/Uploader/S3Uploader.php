<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\S3\S3Client;
use Throwable;

/**
 * Uploads to an S3-compatible bucket (Cloudflare R2, AWS, MinIO, …). Period "folders" are
 * plain key prefixes: <prefix>/daily/<archive>.zip.
 *
 * On days when one archive goes into several periods (Monday → daily + weekly, …), only the
 * first upload transfers data; the rest are server-side copies within the bucket.
 *
 * Like Google Drive, the bucket is rotated by the uploader itself right after a successful
 * upload, keeping the newest N archives per period (0 = keep all). A rotation failure does
 * not fail the upload — it is reported as a warning in the backup log.
 */
class S3Uploader implements IBackupUploader {
    /** @var array<string, S3Client> one client per endpoint + bucket + key */
    private array $clients = [];

    /** @var array<string, string> local file + bucket => key it was already uploaded under */
    private array $uploadedKeys = [];

    public function supports(DestinationConfig $destination): bool {
        return $destination instanceof S3DestinationConfig;
    }

    public function upload(DestinationConfig $destination, UploadTarget $target): UploadResult {
        if (!$destination instanceof S3DestinationConfig) {
            return UploadResult::fail('S3Uploader received an incompatible destination.');
        }

        foreach (['endpoint' => 'ENDPOINT', 'accessKeyId' => 'ACCESS_KEY_ID', 'secretAccessKey' => 'SECRET_ACCESS_KEY'] as $property => $suffix) {
            if ($destination->$property === '') {
                return UploadResult::fail('S3: BACKUP_S3_' . $suffix . ' is not set.');
            }
        }

        $folder = $this->buildFolder($destination, $target->periodFolder);
        $key = $folder . '/' . $target->remoteName;
        $uploadId = $this->getClientId($destination) . '|' . $target->localFile;

        try {
            $client = $this->getClient($destination);

            if (isset($this->uploadedKeys[$uploadId])) {
                $client->copyObject($this->uploadedKeys[$uploadId], $key);
            } else {
                $client->uploadFile($target->localFile, $key);
                $this->uploadedKeys[$uploadId] = $key;
            }
        } catch (Throwable $e) {
            // S3Client messages already start with "S3: …".
            return UploadResult::fail($e->getMessage());
        }

        return UploadResult::ok(null, $this->rotate($client, $destination, $folder, $target->periodFolder));
    }

    /**
     * Deletes everything but the newest keep-count archives in the period folder.
     * Returns a warning string on failure, null on success or when rotation is off.
     */
    private function rotate(
        S3Client $client,
        S3DestinationConfig $destination,
        string $folder,
        string $periodFolder,
    ): ?string {
        $keep = $destination->getKeepCount($periodFolder);

        if ($keep <= 0) {
            return null;
        }

        try {
            // Only direct children of the period folder — never anything nested deeper.
            $objects = array_filter(
                $client->listObjects($folder . '/'),
                static fn(array $object): bool => !str_contains(substr($object['key'], strlen($folder) + 1), '/')
            );

            usort(
                $objects,
                static fn(array $a, array $b): int =>
                    [$b['lastModified'], $b['key']] <=> [$a['lastModified'], $a['key']]
            );

            foreach (array_slice($objects, $keep) as $stale) {
                $client->deleteObject($stale['key']);
            }

            return null;
        } catch (Throwable $e) {
            return 'S3: uploaded fine, but rotation of "' . $periodFolder . '" failed: ' . $e->getMessage();
        }
    }

    private function buildFolder(S3DestinationConfig $destination, string $periodFolder): string {
        $prefix = trim($destination->prefix, '/');

        return $prefix !== '' ? $prefix . '/' . $periodFolder : $periodFolder;
    }

    private function getClient(S3DestinationConfig $destination): S3Client {
        return $this->clients[$this->getClientId($destination)] ??= $this->createClient($destination);
    }

    protected function createClient(S3DestinationConfig $destination): S3Client {
        return new S3Client($destination);
    }

    private function getClientId(S3DestinationConfig $destination): string {
        return $destination->endpoint . '|' . $destination->bucket . '|' . $destination->accessKeyId;
    }
}
