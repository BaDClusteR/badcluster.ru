<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\S3\S3Client;
use BC\Modules\Backup\Uploader\S3Uploader;
use BC\Tests\Fixture\Backup\FakeS3Client;
use BC\Tests\Fixture\Backup\S3UploaderSpy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(S3Uploader::class)]
final class S3UploaderTest extends TestCase {
    public function testSupportsS3Only(): void {
        $uploader = new S3Uploader();

        self::assertTrue($uploader->supports($this->createDestination()));
        self::assertFalse($uploader->supports(new FtpDestinationConfig('FTP', 'host', 21, 'user', 'password')));
    }

    public function testRejectsIncompatibleDestination(): void {
        $uploader = new S3UploaderSpy();

        self::assertEquals(
            UploadResult::fail('S3Uploader received an incompatible destination.'),
            $uploader->upload(new FtpDestinationConfig('FTP', 'host', 21, 'user', 'password'), $this->createTarget())
        );
        self::assertSame([], $uploader->createdClients);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function provideMissingSettings(): iterable {
        yield 'endpoint' => ['', 'id', 'secret', 'S3: BACKUP_S3_ENDPOINT is not set.'];
        yield 'access key' => ['https://s3.example', '', 'secret', 'S3: BACKUP_S3_ACCESS_KEY_ID is not set.'];
        yield 'secret' => ['https://s3.example', 'id', '', 'S3: BACKUP_S3_SECRET_ACCESS_KEY is not set.'];
        yield 'all: the endpoint first' => ['', '', '', 'S3: BACKUP_S3_ENDPOINT is not set.'];
    }

    #[DataProvider('provideMissingSettings')]
    public function testMissingSettings(string $endpoint, string $accessKeyId, string $secret, string $error): void {
        $uploader = new S3UploaderSpy();
        $destination = new S3DestinationConfig('S3', $endpoint, 'bucket', $accessKeyId, $secret);

        self::assertEquals(UploadResult::fail($error), $uploader->upload($destination, $this->createTarget()));
        self::assertSame([], $uploader->createdClients);
    }

    public function testUploadAndRotation(): void {
        $uploader = new S3UploaderSpy();
        $uploader->setUpClient = static function (FakeS3Client $client): void {
            $client->objects = [
                ['key' => 'bc/daily/bc-backup-1.zip', 'lastModified' => '2026-07-01T04:00:00.000Z'],
                ['key' => 'bc/daily/bc-backup-4.zip', 'lastModified' => '2026-07-04T04:00:00.000Z'],
                ['key' => 'bc/daily/nested/old.zip', 'lastModified' => '2020-01-01T00:00:00.000Z'],
                ['key' => 'bc/daily/bc-backup-2.zip', 'lastModified' => '2026-07-02T04:00:00.000Z'],
                ['key' => 'bc/daily/bc-backup-3b.zip', 'lastModified' => '2026-07-03T04:00:00.000Z'],
                ['key' => 'bc/daily/bc-backup-3a.zip', 'lastModified' => '2026-07-03T04:00:00.000Z'],
            ];
        };

        $result = $uploader->upload($this->createDestination(prefix: '/bc/'), $this->createTarget());

        self::assertEquals(UploadResult::ok(), $result);
        self::assertCount(1, $uploader->createdClients);
        self::assertSame(
            [
                'upload /tmp/bc-backup.zip bc/daily/bc-backup.zip',
                'list bc/daily/',
                // The newest three are kept (by the time, then by the key), nested objects are never touched.
                'delete bc/daily/bc-backup-2.zip',
                'delete bc/daily/bc-backup-1.zip',
            ],
            $uploader->createdClients[0]->calls
        );
    }

    public function testRotationKeepsEverythingWithinLimit(): void {
        $uploader = new S3UploaderSpy();
        $uploader->setUpClient = static function (FakeS3Client $client): void {
            $client->objects = [
                ['key' => 'daily/a.zip', 'lastModified' => '2026-07-01T04:00:00.000Z'],
                ['key' => 'daily/b.zip', 'lastModified' => '2026-07-02T04:00:00.000Z'],
            ];
        };

        $uploader->upload($this->createDestination(keepDaily: 2), $this->createTarget());

        self::assertSame(['upload /tmp/bc-backup.zip daily/bc-backup.zip', 'list daily/'], $uploader->createdClients[0]->calls);
    }

    public function testNoRotationWhenKeepingAll(): void {
        $uploader = new S3UploaderSpy();

        $result = $uploader->upload($this->createDestination(keepDaily: 0), $this->createTarget());

        self::assertEquals(UploadResult::ok(), $result);
        self::assertSame(['upload /tmp/bc-backup.zip daily/bc-backup.zip'], $uploader->createdClients[0]->calls);
    }

    public function testOtherPeriodsAreServerSideCopies(): void {
        $uploader = new S3UploaderSpy();
        $destination = $this->createDestination(prefix: 'bc', keepWeekly: 0, keepMonthly: 0);

        $uploader->upload($destination, $this->createTarget('weekly'));
        $uploader->upload($destination, $this->createTarget('monthly'));
        // The same destination as a new object: the same client.
        $uploader->upload($this->createDestination(prefix: 'bc', keepWeekly: 0, keepMonthly: 0), $this->createTarget('monthly'));

        self::assertCount(1, $uploader->createdClients);
        self::assertSame(
            [
                'upload /tmp/bc-backup.zip bc/weekly/bc-backup.zip',
                'copy bc/weekly/bc-backup.zip bc/monthly/bc-backup.zip',
                'copy bc/weekly/bc-backup.zip bc/monthly/bc-backup.zip',
            ],
            $uploader->createdClients[0]->calls
        );
    }

    public function testOtherArchiveIsUploaded(): void {
        $uploader = new S3UploaderSpy();
        $destination = $this->createDestination(keepDaily: 0);

        $uploader->upload($destination, $this->createTarget());
        $uploader->upload($destination, new UploadTarget('/tmp/other.zip', 'daily', 'other.zip'));

        self::assertSame(
            ['upload /tmp/bc-backup.zip daily/bc-backup.zip', 'upload /tmp/other.zip daily/other.zip'],
            $uploader->createdClients[0]->calls
        );
    }

    /**
     * @return iterable<string, array{S3DestinationConfig}>
     */
    public static function provideOtherClients(): iterable {
        yield 'other endpoint' => [new S3DestinationConfig('S3', 'https://other.example', 'bucket', 'id', 'secret', keepDaily: 0)];
        yield 'other bucket' => [new S3DestinationConfig('S3', 'https://s3.example', 'other', 'id', 'secret', keepDaily: 0)];
        yield 'other key' => [new S3DestinationConfig('S3', 'https://s3.example', 'bucket', 'other', 'secret', keepDaily: 0)];
    }

    /**
     * Another bucket has no copy of the archive yet.
     */
    #[DataProvider('provideOtherClients')]
    public function testOtherClientUploads(S3DestinationConfig $other): void {
        $uploader = new S3UploaderSpy();

        $uploader->upload($this->createDestination(keepDaily: 0), $this->createTarget());
        $uploader->upload($other, $this->createTarget());

        self::assertCount(2, $uploader->createdClients);
        self::assertSame(['upload /tmp/bc-backup.zip daily/bc-backup.zip'], $uploader->createdClients[1]->calls);
        self::assertSame($other, $uploader->createdClients[1]->config);
    }

    /**
     * @return iterable<string, array{S3DestinationConfig, S3DestinationConfig}>
     */
    public static function provideLookAlikeClients(): iterable {
        yield 'endpoint + bucket' => [
            new S3DestinationConfig('S3', 'https://s3.example', 'ab', 'id', 'secret', keepDaily: 0),
            new S3DestinationConfig('S3', 'https://s3.examplea', 'b', 'id', 'secret', keepDaily: 0),
        ];
        yield 'bucket + key' => [
            new S3DestinationConfig('S3', 'https://s3.example', 'b', 'id', 'secret', keepDaily: 0),
            new S3DestinationConfig('S3', 'https://s3.example', 'bi', 'd', 'secret', keepDaily: 0),
        ];
    }

    /**
     * The parts of the client ID are separated: the clients never mix up.
     */
    #[DataProvider('provideLookAlikeClients')]
    public function testLookAlikeClientsAreDifferent(S3DestinationConfig $first, S3DestinationConfig $second): void {
        $uploader = new S3UploaderSpy();

        $uploader->upload($first, $this->createTarget());
        $uploader->upload($second, $this->createTarget());

        self::assertCount(2, $uploader->createdClients);
        self::assertSame(['upload /tmp/bc-backup.zip daily/bc-backup.zip'], $uploader->createdClients[1]->calls);
    }

    public function testLookAlikeUploadsAreDifferent(): void {
        $uploader = new S3UploaderSpy();

        $uploader->upload(
            new S3DestinationConfig('S3', 'https://s3.example', 'bucket', 'id', 'secret', keepDaily: 0),
            new UploadTarget('/x/a.zip', 'daily', 'a.zip')
        );
        $uploader->upload(
            new S3DestinationConfig('S3', 'https://s3.example', 'bucket', 'id/x', 'secret', keepDaily: 0),
            new UploadTarget('/a.zip', 'daily', 'a.zip')
        );

        self::assertSame(['upload /a.zip daily/a.zip'], $uploader->createdClients[1]->calls);
    }

    public function testRotationSkipsEmptyNestedFolder(): void {
        $uploader = new S3UploaderSpy();
        $uploader->setUpClient = static function (FakeS3Client $client): void {
            $client->objects = [
                ['key' => 'daily//nested.zip', 'lastModified' => '1'],
                ['key' => 'daily/a.zip', 'lastModified' => '2'],
                ['key' => 'daily/b.zip', 'lastModified' => '3'],
            ];
        };

        $uploader->upload($this->createDestination(keepDaily: 1), $this->createTarget());

        self::assertSame(
            ['upload /tmp/bc-backup.zip daily/bc-backup.zip', 'list daily/', 'delete daily/a.zip'],
            $uploader->createdClients[0]->calls
        );
    }

    public function testRotationOrderOfSameTime(): void {
        $uploader = new S3UploaderSpy();
        $uploader->setUpClient = static function (FakeS3Client $client): void {
            $client->objects = [
                ['key' => 'daily/a.zip', 'lastModified' => '2'],
                ['key' => 'daily/c.zip', 'lastModified' => '1'],
                ['key' => 'daily/d.zip', 'lastModified' => '2'],
                ['key' => 'daily/b.zip', 'lastModified' => '2'],
            ];
        };

        $uploader->upload($this->createDestination(keepDaily: 2), $this->createTarget());

        self::assertSame(
            ['upload /tmp/bc-backup.zip daily/bc-backup.zip', 'list daily/', 'delete daily/a.zip', 'delete daily/c.zip'],
            $uploader->createdClients[0]->calls
        );
    }

    public function testFailedUpload(): void {
        $uploader = new S3UploaderSpy();
        $uploader->setUpClient = static function (FakeS3Client $client): void {
            $client->failures['upload'] = 'S3: PUT daily/bc-backup.zip failed after 5 attempts: timeout';
        };
        $destination = $this->createDestination();

        $result = $uploader->upload($destination, $this->createTarget());

        self::assertEquals(UploadResult::fail('S3: PUT daily/bc-backup.zip failed after 5 attempts: timeout'), $result);
        self::assertSame(['upload /tmp/bc-backup.zip daily/bc-backup.zip'], $uploader->createdClients[0]->calls, 'No rotation.');

        // Nothing was uploaded: the next period is an upload again, not a copy.
        $uploader->upload($destination, $this->createTarget('weekly'));
        self::assertSame(
            ['upload /tmp/bc-backup.zip daily/bc-backup.zip', 'upload /tmp/bc-backup.zip weekly/bc-backup.zip'],
            $uploader->createdClients[0]->calls
        );
    }

    public function testFailedCopy(): void {
        $uploader = new S3UploaderSpy();
        $uploader->setUpClient = static function (FakeS3Client $client): void {
            $client->failures['copy'] = 'S3: copy failed';
        };
        $destination = $this->createDestination(keepDaily: 0);

        $uploader->upload($destination, $this->createTarget());

        self::assertEquals(UploadResult::fail('S3: copy failed'), $uploader->upload($destination, $this->createTarget('weekly')));
    }

    public function testInvalidEndpointFails(): void {
        $result = new S3Uploader()->upload(
            new S3DestinationConfig('S3', 'not a url', 'bucket', 'id', 'secret'),
            $this->createTarget()
        );

        self::assertEquals(UploadResult::fail('S3: BACKUP_S3_ENDPOINT is not a valid URL: not a url'), $result);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRotationFailures(): iterable {
        yield 'listing' => ['list', 'S3: uploaded fine, but rotation of "daily" failed: S3: listing failed'];
        yield 'deletion' => ['delete', 'S3: uploaded fine, but rotation of "daily" failed: S3: deletion failed'];
    }

    #[DataProvider('provideRotationFailures')]
    public function testFailedRotationIsWarning(string $method, string $warning): void {
        $uploader = new S3UploaderSpy();
        $uploader->setUpClient = static function (FakeS3Client $client) use ($method): void {
            $client->objects = [
                ['key' => 'daily/a.zip', 'lastModified' => '1'],
                ['key' => 'daily/b.zip', 'lastModified' => '2'],
            ];
            $client->failures[$method] = $method === 'list' ? 'S3: listing failed' : 'S3: deletion failed';
        };

        $result = $uploader->upload($this->createDestination(keepDaily: 1), $this->createTarget());

        self::assertEquals(UploadResult::ok(null, $warning), $result);
    }

    public function testClientIsCreatedForDestination(): void {
        $destination = $this->createDestination();
        $client = new ReflectionMethod(S3Uploader::class, 'createClient')->invoke(new S3Uploader(), $destination);

        self::assertInstanceOf(S3Client::class, $client);
        self::assertNotInstanceOf(FakeS3Client::class, $client);
    }

    private function createDestination(
        string $prefix = '',
        int    $keepDaily = 3,
        int    $keepWeekly = 1,
        int    $keepMonthly = 1
    ): S3DestinationConfig {
        return new S3DestinationConfig(
            name: 'S3',
            endpoint: 'https://s3.example',
            bucket: 'bucket',
            accessKeyId: 'id',
            secretAccessKey: 'secret',
            prefix: $prefix,
            keepDaily: $keepDaily,
            keepWeekly: $keepWeekly,
            keepMonthly: $keepMonthly
        );
    }

    private function createTarget(string $period = 'daily'): UploadTarget {
        return new UploadTarget('/tmp/bc-backup.zip', $period, 'bc-backup.zip');
    }
}
