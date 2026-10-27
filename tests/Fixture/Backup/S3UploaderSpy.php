<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Uploader\S3\S3Client;
use BC\Modules\Backup\Uploader\S3Uploader;

/**
 * The uploader with FakeS3Client: one per created client, in creation order.
 */
final class S3UploaderSpy extends S3Uploader {
    /** @var list<FakeS3Client> */
    public array $createdClients = [];

    /** @var callable(FakeS3Client): void|null Sets up a new client (the listing, the failures). */
    public $setUpClient = null;

    protected function createClient(S3DestinationConfig $destination): S3Client {
        $client = new FakeS3Client($destination);

        if ($this->setUpClient !== null) {
            ($this->setUpClient)($client);
        }

        return $this->createdClients[] = $client;
    }
}
