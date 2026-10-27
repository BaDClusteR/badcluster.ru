<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Uploader\S3\S3Client;

/**
 * A bucket in memory: records the calls, the listing is given by the test.
 */
final class FakeS3Client extends S3Client {
    /** @var list<string> E.g. "upload /tmp/a.zip daily/a.zip". */
    public array $calls = [];

    /** @var array<array{key: string, lastModified: string}> */
    public array $objects = [];

    /** @var array<string, string> Method => the message of the BackupException it throws. */
    public array $failures = [];

    public function __construct(
        public readonly S3DestinationConfig $config
    ) {
        parent::__construct($config);
    }

    public function uploadFile(string $localFile, string $key): void {
        $this->call('upload', "upload $localFile $key");
    }

    public function copyObject(string $sourceKey, string $key): void {
        $this->call('copy', "copy $sourceKey $key");
    }

    public function listObjects(string $prefix): array {
        $this->call('list', "list $prefix");

        return $this->objects;
    }

    public function deleteObject(string $key): void {
        $this->call('delete', "delete $key");
    }

    private function call(string $method, string $call): void {
        $this->calls[] = $call;

        if (isset($this->failures[$method])) {
            throw new BackupException($this->failures[$method]);
        }
    }
}
