<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\Destination\GoogleDriveDestinationConfig;
use BC\Modules\Backup\Uploader\GoogleDrive\GoogleDriveClient;
use BC\Modules\Backup\Uploader\GoogleDriveUploader;

/**
 * The uploader with FakeGoogleDriveClient: one per created client, in creation order.
 */
final class GoogleDriveUploaderSpy extends GoogleDriveUploader {
    /** @var list<FakeGoogleDriveClient> */
    public array $createdClients = [];

    /** @var callable(FakeGoogleDriveClient): void|null Sets up a new client. */
    public $setUpClient = null;

    protected function createClient(GoogleDriveDestinationConfig $destination): GoogleDriveClient {
        $client = new FakeGoogleDriveClient($destination->keyFilePath);

        if ($this->setUpClient !== null) {
            ($this->setUpClient)($client);
        }

        return $this->createdClients[] = $client;
    }
}
