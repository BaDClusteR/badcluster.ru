<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Uploader\GoogleDrive\GoogleDriveClient;

/**
 * A Drive in memory: records the calls, the folders and the listing are given by the test.
 */
final class FakeGoogleDriveClient extends GoogleDriveClient {
    /** @var list<string> E.g. "find parent daily". */
    public array $calls = [];

    /** @var array<string, string> Name of a child folder => its ID. */
    public array $folders = [];

    /** @var list<array{id: string, name: string, createdTime: string}> Newest first. */
    public array $files = [];

    /** @var array{id: string, webViewLink?: string} */
    public array $uploadedFile = ['id' => 'file-id', 'webViewLink' => 'https://drive.example/file-id'];

    /** @var array<string, string> Method => the message of the BackupException it throws. */
    public array $failures = [];

    public function __construct(
        public readonly string $keyFilePathOfClient
    ) {
        parent::__construct($keyFilePathOfClient);
    }

    public function findChildFolder(string $parentId, string $name): ?string {
        $this->call('find', "find $parentId $name");

        return $this->folders[$name] ?? null;
    }

    public function createFolder(string $parentId, string $name): string {
        $this->call('create', "create $parentId $name");

        return "new-$name";
    }

    public function uploadFile(string $localFile, string $parentId, string $name): array {
        $this->call('upload', "upload $localFile $parentId $name");

        return $this->uploadedFile;
    }

    public function listFiles(string $parentId): array {
        $this->call('list', "list $parentId");

        return $this->files;
    }

    public function deleteFile(string $fileId): void {
        $this->call('delete', "delete $fileId");
    }

    private function call(string $method, string $call): void {
        $this->calls[] = $call;

        if (isset($this->failures[$method])) {
            throw new BackupException($this->failures[$method]);
        }
    }
}
