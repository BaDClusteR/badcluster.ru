<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\GoogleDriveDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\GoogleDrive\GoogleDriveClient;
use BC\Modules\Backup\Uploader\GoogleDriveUploader;
use BC\Tests\Fixture\Backup\FakeGoogleDriveClient;
use BC\Tests\Fixture\Backup\GoogleDriveUploaderSpy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(GoogleDriveUploader::class)]
final class GoogleDriveUploaderTest extends TestCase {
    public function testSupportsGoogleDriveOnly(): void {
        $uploader = new GoogleDriveUploader();

        self::assertTrue($uploader->supports($this->createDestination()));
        self::assertFalse($uploader->supports(new FtpDestinationConfig('FTP', 'host', 21, 'user', 'password')));
    }

    public function testRejectsIncompatibleDestination(): void {
        $uploader = new GoogleDriveUploaderSpy();

        self::assertEquals(
            UploadResult::fail('GoogleDriveUploader received an incompatible destination.'),
            $uploader->upload(new FtpDestinationConfig('FTP', 'host', 21, 'user', 'password'), $this->createTarget())
        );
        self::assertSame([], $uploader->createdClients);
    }

    public function testFolderIsRequired(): void {
        $uploader = new GoogleDriveUploaderSpy();

        self::assertEquals(
            UploadResult::fail('Google Drive: BACKUP_GDRIVE_FOLDER_ID is not set.'),
            $uploader->upload($this->createDestination(folderId: ''), $this->createTarget())
        );
        self::assertSame([], $uploader->createdClients);
    }

    public function testUploadToExistingFolderAndRotation(): void {
        $uploader = new GoogleDriveUploaderSpy();
        $uploader->setUpClient = static function (FakeGoogleDriveClient $client): void {
            $client->folders = ['daily' => 'daily-id'];
            $client->files = [
                ['id' => 'f4', 'name' => '4.zip', 'createdTime' => '4'],
                ['id' => 'f3', 'name' => '3.zip', 'createdTime' => '3'],
                ['id' => 'f2', 'name' => '2.zip', 'createdTime' => '2'],
                ['id' => 'f1', 'name' => '1.zip', 'createdTime' => '1'],
            ];
        };

        $result = $uploader->upload($this->createDestination(keepDaily: 2), $this->createTarget());

        self::assertEquals(UploadResult::ok('https://drive.example/file-id'), $result);
        self::assertSame(
            [
                'find root-id daily',
                'upload /tmp/bc-backup.zip daily-id bc-backup.zip',
                'list daily-id',
                'delete f2',
                'delete f1',
            ],
            $uploader->createdClients[0]->calls
        );
    }

    public function testCreatesMissingFolder(): void {
        $uploader = new GoogleDriveUploaderSpy();

        $uploader->upload($this->createDestination(), $this->createTarget('yearly'));

        self::assertSame(
            ['find root-id yearly', 'create root-id yearly', 'upload /tmp/bc-backup.zip new-yearly bc-backup.zip'],
            $uploader->createdClients[0]->calls,
            'No rotation: the yearly archives are kept.'
        );
    }

    public function testRotationWithinLimit(): void {
        $uploader = new GoogleDriveUploaderSpy();
        $uploader->setUpClient = static function (FakeGoogleDriveClient $client): void {
            $client->folders = ['weekly' => 'weekly-id'];
            $client->files = [['id' => 'f1', 'name' => '1.zip', 'createdTime' => '1']];
        };

        $uploader->upload($this->createDestination(), $this->createTarget('weekly'));

        self::assertSame('list weekly-id', end($uploader->createdClients[0]->calls));
    }

    public function testFileWithoutLink(): void {
        $uploader = new GoogleDriveUploaderSpy();
        $uploader->setUpClient = static function (FakeGoogleDriveClient $client): void {
            $client->uploadedFile = ['id' => 'file-id'];
        };

        self::assertEquals(
            UploadResult::ok(),
            $uploader->upload($this->createDestination(keepDaily: 0), $this->createTarget())
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUploadSteps(): iterable {
        yield 'finding the folder' => ['find'];
        yield 'creating the folder' => ['create'];
        yield 'uploading' => ['upload'];
    }

    #[DataProvider('provideUploadSteps')]
    public function testFailedUpload(string $step): void {
        $uploader = new GoogleDriveUploaderSpy();
        $uploader->setUpClient = static function (FakeGoogleDriveClient $client) use ($step): void {
            $client->failures[$step] = 'Google Drive: HTTP 403';
        };

        self::assertEquals(
            UploadResult::fail('Google Drive upload failed: Google Drive: HTTP 403'),
            $uploader->upload($this->createDestination(), $this->createTarget())
        );
        self::assertNotContains('list new-daily', $uploader->createdClients[0]->calls);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideRotationSteps(): iterable {
        yield 'listing' => ['list'];
        yield 'deleting' => ['delete'];
    }

    #[DataProvider('provideRotationSteps')]
    public function testFailedRotationIsWarning(string $step): void {
        $uploader = new GoogleDriveUploaderSpy();
        $uploader->setUpClient = static function (FakeGoogleDriveClient $client) use ($step): void {
            $client->files = [
                ['id' => 'f2', 'name' => '2.zip', 'createdTime' => '2'],
                ['id' => 'f1', 'name' => '1.zip', 'createdTime' => '1'],
            ];
            $client->failures[$step] = 'Google Drive: HTTP 500';
        };

        self::assertEquals(
            UploadResult::ok(
                'https://drive.example/file-id',
                'Google Drive: uploaded fine, but rotation of "monthly" failed: Google Drive: HTTP 500'
            ),
            $uploader->upload($this->createDestination(keepMonthly: 1), $this->createTarget('monthly'))
        );
    }

    public function testOneClientPerKeyFile(): void {
        $uploader = new GoogleDriveUploaderSpy();

        $uploader->upload($this->createDestination(keepDaily: 0), $this->createTarget());
        $uploader->upload($this->createDestination(keepDaily: 0), $this->createTarget('weekly'));
        $uploader->upload($this->createDestination(keyFile: '/keys/other.json'), $this->createTarget());

        self::assertCount(2, $uploader->createdClients);
        self::assertSame('/keys/drive.json', $uploader->createdClients[0]->keyFilePathOfClient);
        self::assertSame('/keys/other.json', $uploader->createdClients[1]->keyFilePathOfClient);
        // daily: find, create, upload; weekly: find, create, upload, list (the weekly archive is rotated).
        self::assertCount(7, $uploader->createdClients[0]->calls);
    }

    public function testClientIsCreatedForDestination(): void {
        $client = new ReflectionMethod(GoogleDriveUploader::class, 'createClient')
            ->invoke(new GoogleDriveUploader(), $this->createDestination());

        self::assertInstanceOf(GoogleDriveClient::class, $client);
        self::assertNotInstanceOf(FakeGoogleDriveClient::class, $client);
    }

    private function createDestination(
        string $folderId = 'root-id',
        int    $keepDaily = 3,
        int    $keepMonthly = 1,
        string $keyFile = '/keys/drive.json'
    ): GoogleDriveDestinationConfig {
        return new GoogleDriveDestinationConfig(
            name: 'Drive',
            keyFilePath: $keyFile,
            folderId: $folderId,
            keepDaily: $keepDaily,
            keepWeekly: 1,
            keepMonthly: $keepMonthly,
            keepYearly: 0
        );
    }

    private function createTarget(string $period = 'daily'): UploadTarget {
        return new UploadTarget('/tmp/bc-backup.zip', $period, 'bc-backup.zip');
    }
}
