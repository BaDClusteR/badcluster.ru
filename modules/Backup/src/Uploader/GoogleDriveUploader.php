<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Config\Destination\GoogleDriveDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\GoogleDrive\GoogleDriveClient;
use Throwable;

/**
 * Uploads to a Google Drive folder shared with a service account, creating the period
 * sub-folders (daily/weekly/…) on demand.
 *
 * Unlike SFTP (delete-proof server-side), Drive is treated as a convenience mirror: after a
 * successful upload the uploader ROTATES the period folder itself, keeping only the newest
 * N archives per the destination's keep-counts (0 = keep all). A rotation failure does not
 * fail the upload — it is reported as a warning in the backup log.
 */
class GoogleDriveUploader implements IBackupUploader {
    /** @var array<string, GoogleDriveClient> one authenticated client per key file */
    private array $clients = [];

    public function supports(DestinationConfig $destination): bool {
        return $destination instanceof GoogleDriveDestinationConfig;
    }

    public function upload(DestinationConfig $destination, UploadTarget $target): UploadResult {
        if (!$destination instanceof GoogleDriveDestinationConfig) {
            return UploadResult::fail('GoogleDriveUploader received an incompatible destination.');
        }

        if ($destination->folderId === '') {
            return UploadResult::fail('Google Drive: BACKUP_GDRIVE_FOLDER_ID is not set.');
        }

        try {
            $client = $this->getClient($destination);

            $periodFolderId = $client->findChildFolder($destination->folderId, $target->periodFolder)
                ?? $client->createFolder($destination->folderId, $target->periodFolder);

            $file = $client->uploadFile($target->localFile, $periodFolderId, $target->remoteName);
        } catch (Throwable $e) {
            return UploadResult::fail('Google Drive upload failed: ' . $e->getMessage());
        }

        $warning = $this->rotate($client, $destination, $periodFolderId, $target->periodFolder);

        return UploadResult::ok($file['webViewLink'] ?? null, $warning);
    }

    /**
     * Deletes everything but the newest keep-count files in the period folder.
     * Returns a warning string on failure, null on success or when rotation is off.
     */
    private function rotate(
        GoogleDriveClient $client,
        GoogleDriveDestinationConfig $destination,
        string $periodFolderId,
        string $periodFolder,
    ): ?string {
        $keep = $destination->getKeepCount($periodFolder);

        if ($keep <= 0) {
            return null;
        }

        try {
            // listFiles() returns newest-first, so everything past $keep is stale.
            foreach (array_slice($client->listFiles($periodFolderId), $keep) as $stale) {
                $client->deleteFile($stale['id']);
            }

            return null;
        } catch (Throwable $e) {
            return 'Google Drive: uploaded fine, but rotation of "' . $periodFolder . '" failed: '
                . $e->getMessage();
        }
    }

    private function getClient(GoogleDriveDestinationConfig $destination): GoogleDriveClient {
        return $this->clients[$destination->keyFilePath]
            ??= new GoogleDriveClient($destination->keyFilePath);
    }
}
