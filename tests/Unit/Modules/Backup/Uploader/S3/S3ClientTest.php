<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader\S3;

use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Uploader\S3\RetryableS3Exception;
use BC\Modules\Backup\Uploader\S3\S3Client;
use BC\Tests\Fixture\Backup\Quietly;
use BC\Tests\Fixture\Backup\S3ClientSpy;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

/**
 * Everything but the wire: the requests go to S3ClientSpy.
 */
#[CoversClass(S3Client::class)]
#[CoversClass(RetryableS3Exception::class)]
final class S3ClientTest extends TestCase {
    use Quietly;

    private const int MIB = 1024 * 1024;

    private string $dir;

    protected function setUp(): void {
        $this->dir = TempDir::create();
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideEndpoints(): iterable {
        yield 'https' => ['https://account.r2.example', 'account.r2.example', 'https://account.r2.example'];
        yield 'with a port and a path' => ['http://minio:9000/ignored', 'minio:9000', 'http://minio:9000'];
        yield 'no scheme is https' => ['//s3.example', 's3.example', 'https://s3.example'];
    }

    #[DataProvider('provideEndpoints')]
    public function testEndpoint(string $endpoint, string $host, string $baseUrl): void {
        $client = new S3Client($this->createConfig(endpoint: $endpoint));

        self::assertSame($host, new ReflectionProperty(S3Client::class, 'host')->getValue($client));
        self::assertSame($baseUrl, new ReflectionProperty(S3Client::class, 'baseUrl')->getValue($client));
    }

    public function testInvalidEndpoint(): void {
        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('S3: BACKUP_S3_ENDPOINT is not a valid URL: s3.example');

        new S3Client($this->createConfig(endpoint: 's3.example'));
    }

    public function testSmallFileIsOnePut(): void {
        $file = $this->createFile('archive', 10);
        $client = $this->createClient()->queue(S3ClientSpy::response());

        $client->uploadFile($file, 'bc/daily/my archive+1.zip');

        self::assertSame(
            [
                [
                    'method'      => 'PUT',
                    'path'        => '/bucket/bc/daily/my%20archive%2B1.zip',
                    'query'       => [],
                    'body'        => '',
                    'fileSlice'   => [$file, 0, 70],
                    'headers'     => [],
                    'payloadHash' => hash('sha256', str_repeat('archive', 10)),
                ],
            ],
            $client->requests
        );
    }

    public function testFileOfExactlyOnePartIsOnePut(): void {
        $file = $this->createFileOfSize(5 * self::MIB);
        $client = $this->createClient(partSizeMb: 1)->queue(S3ClientSpy::response());

        $client->uploadFile($file, 'a.zip');

        self::assertCount(1, $client->requests);
        self::assertSame([$file, 0, 5 * self::MIB], $client->requests[0]['fileSlice']);
    }

    public function testPartSizeOfConfigAboveMinimum(): void {
        $file = $this->createFileOfSize(6 * self::MIB);
        $client = $this->createClient(partSizeMb: 6)->queue(S3ClientSpy::response());

        $client->uploadFile($file, 'a.zip');

        self::assertCount(1, $client->requests);
    }

    public function testMultipartUpload(): void {
        $file = $this->createFileOfSize(5 * self::MIB + 3);
        $client = $this->createClient(partSizeMb: 0)->queue(
            S3ClientSpy::response('<InitiateMultipartUploadResult><UploadId>upload-1</UploadId></InitiateMultipartUploadResult>'),
            S3ClientSpy::response('', ['etag' => '"etag<1&"']),
            S3ClientSpy::response('', ['etag' => '"etag-2"']),
            S3ClientSpy::response('<CompleteMultipartUploadResult/>'),
        );

        $client->uploadFile($file, 'daily/a.zip');

        self::assertCount(4, $client->requests);
        [$create, $part1, $part2, $complete] = $client->requests;

        self::assertSame(['POST', '/bucket/daily/a.zip', ['uploads' => ''], null], [
            $create['method'],
            $create['path'],
            $create['query'],
            $create['fileSlice'],
        ]);
        self::assertSame(hash('sha256', ''), $create['payloadHash']);

        self::assertSame('PUT', $part1['method']);
        self::assertSame(['partNumber' => '1', 'uploadId' => 'upload-1'], $part1['query']);
        self::assertSame([$file, 0, 5 * self::MIB], $part1['fileSlice']);
        self::assertSame(hash('sha256', str_repeat('x', 5 * self::MIB)), $part1['payloadHash']);

        self::assertSame(['partNumber' => '2', 'uploadId' => 'upload-1'], $part2['query']);
        self::assertSame([$file, 5 * self::MIB, 3], $part2['fileSlice']);
        self::assertSame(hash('sha256', 'xxx'), $part2['payloadHash']);

        $expectedBody = '<CompleteMultipartUpload>'
            . '<Part><PartNumber>1</PartNumber><ETag>"etag&lt;1&amp;"</ETag></Part>'
            . '<Part><PartNumber>2</PartNumber><ETag>"etag-2"</ETag></Part>'
            . '</CompleteMultipartUpload>';
        self::assertSame(['POST', ['uploadId' => 'upload-1'], $expectedBody, hash('sha256', $expectedBody)], [
            $complete['method'],
            $complete['query'],
            $complete['body'],
            $complete['payloadHash'],
        ]);
    }

    public function testPartWithoutEtagAbortsUpload(): void {
        $file = $this->createFileOfSize(5 * self::MIB + 1);
        $client = $this->createClient(partSizeMb: 5)->queue(
            S3ClientSpy::response('<R><UploadId>upload-1</UploadId></R>'),
            S3ClientSpy::response('', ['etag' => '"etag-1"']),
            S3ClientSpy::response(),
            S3ClientSpy::response(),
        );

        $exception = $this->catch(static fn() => $client->uploadFile($file, 'a.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('S3: part 2 was accepted without an ETag.', $exception->getMessage());
        $abort = end($client->requests);
        self::assertNotFalse($abort);
        self::assertSame(['DELETE', '/bucket/a.zip', ['uploadId' => 'upload-1']], [$abort['method'], $abort['path'], $abort['query']]);
        self::assertCount(4, $client->requests);
    }

    public function testFailedAbortKeepsOriginalError(): void {
        $file = $this->createFileOfSize(5 * self::MIB + 1);
        $client = $this->createClient()->queue(
            S3ClientSpy::response('<R><UploadId>upload-1</UploadId></R>'),
            new BackupException('S3: PUT failed'),
            new BackupException('S3: DELETE failed'),
        );

        $exception = $this->catch(static fn() => $client->uploadFile($file, 'a.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('S3: PUT failed', $exception->getMessage());
        self::assertSame(['POST', 'PUT', 'DELETE'], array_column($client->requests, 'method'));
    }

    public function testCompletionErrorInBody(): void {
        $file = $this->createFileOfSize(5 * self::MIB + 1);
        $client = $this->createClient()->queue(
            S3ClientSpy::response('<R><UploadId>upload-1</UploadId></R>'),
            S3ClientSpy::response('', ['etag' => 'e1']),
            S3ClientSpy::response('', ['etag' => 'e2']),
            S3ClientSpy::response('<Error><Code>InternalError</Code><Message>Try again</Message></Error>'),
            S3ClientSpy::response(),
        );

        $exception = $this->catch(static fn() => $client->uploadFile($file, 'a.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('S3: completing upload of a.zip failed: InternalError — Try again', $exception->getMessage());
        self::assertSame('DELETE', end($client->requests)['method'] ?? null);
    }

    public function testMultipartUploadWithoutUploadId(): void {
        $file = $this->createFileOfSize(5 * self::MIB + 1);
        $client = $this->createClient()->queue(S3ClientSpy::response('<R><UploadId></UploadId></R>'));

        $exception = $this->catch(static fn() => $client->uploadFile($file, 'a.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('S3: multipart upload was created without an UploadId.', $exception->getMessage());
        self::assertCount(1, $client->requests, 'Nothing to abort.');
    }

    public function testMissingFile(): void {
        $client = $this->createClient();

        $exception = self::quietly(fn(): ?Throwable => $this->catch(
            fn() => $client->uploadFile("$this->dir/missing.zip", 'a.zip')
        ));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame("S3: cannot stat $this->dir/missing.zip", $exception->getMessage());
        self::assertSame([], $client->requests);
    }

    public function testCopyObject(): void {
        $client = $this->createClient()->queue(S3ClientSpy::response('<CopyObjectResult/>'));

        $client->copyObject('bc/daily/a b.zip', 'bc/weekly/a b.zip');

        self::assertSame(
            [
                'method'      => 'PUT',
                'path'        => '/bucket/bc/weekly/a%20b.zip',
                'query'       => [],
                'body'        => '',
                'fileSlice'   => null,
                'headers'     => ['x-amz-copy-source' => '/bucket/bc/daily/a%20b.zip'],
                'payloadHash' => hash('sha256', ''),
            ],
            $client->requests[0]
        );
    }

    public function testCopyErrorInBody(): void {
        $client = $this->createClient()->queue(S3ClientSpy::response('<Error><Code>SlowDown</Code></Error>'));

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('S3: copy to weekly/a.zip failed: SlowDown');

        $client->copyObject('daily/a.zip', 'weekly/a.zip');
    }

    public function testListObjectsOfAllPages(): void {
        $client = $this->createClient()->queue(
            S3ClientSpy::response(
                '<ListBucketResult><IsTruncated>true</IsTruncated><NextContinuationToken>token-2</NextContinuationToken>'
                . '<Contents><Key>bc/daily/a.zip</Key><LastModified>2026-07-01T04:00:00.000Z</LastModified></Contents>'
                . '<Contents><Key>bc/daily/b.zip</Key><LastModified>2026-07-02T04:00:00.000Z</LastModified></Contents>'
                . '</ListBucketResult>'
            ),
            S3ClientSpy::response(
                '<ListBucketResult><IsTruncated>false</IsTruncated><NextContinuationToken>ignored</NextContinuationToken>'
                . '<Contents><Key>bc/daily/c.zip</Key><LastModified>2026-07-03T04:00:00.000Z</LastModified></Contents>'
                . '</ListBucketResult>'
            ),
        );

        self::assertSame(
            [
                ['key' => 'bc/daily/a.zip', 'lastModified' => '2026-07-01T04:00:00.000Z'],
                ['key' => 'bc/daily/b.zip', 'lastModified' => '2026-07-02T04:00:00.000Z'],
                ['key' => 'bc/daily/c.zip', 'lastModified' => '2026-07-03T04:00:00.000Z'],
            ],
            $client->listObjects('bc/daily/')
        );

        self::assertSame(['GET', '/bucket', ['list-type' => '2', 'prefix' => 'bc/daily/']], [
            $client->requests[0]['method'],
            $client->requests[0]['path'],
            $client->requests[0]['query'],
        ]);
        self::assertSame(
            ['list-type' => '2', 'prefix' => 'bc/daily/', 'continuation-token' => 'token-2'],
            $client->requests[1]['query']
        );
        self::assertCount(2, $client->requests);
    }

    public function testListOfEmptyBucket(): void {
        $client = $this->createClient()->queue(S3ClientSpy::response('<ListBucketResult><IsTruncated>false</IsTruncated></ListBucketResult>'));

        self::assertSame([], $client->listObjects(''));
    }

    public function testListWithNonXmlResponse(): void {
        $client = $this->createClient()->queue(S3ClientSpy::response('Bad gateway'));

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('S3: unexpected non-XML response: Bad gateway');

        $client->listObjects('');
    }

    public function testDeleteObject(): void {
        $client = $this->createClient()->queue(S3ClientSpy::response());

        $client->deleteObject('daily/old.zip');

        self::assertSame(['DELETE', '/bucket/daily/old.zip', [], ''], [
            $client->requests[0]['method'],
            $client->requests[0]['path'],
            $client->requests[0]['query'],
            $client->requests[0]['body'],
        ]);
    }

    public function testRetriesTransientFailures(): void {
        $client = $this->createClient()->queue(
            new RetryableS3Exception('HTTP 503'),
            new RetryableS3Exception('curl error'),
            S3ClientSpy::response(),
        );

        $client->deleteObject('a.zip');

        self::assertCount(3, $client->requests);
        self::assertSame([2, 4], $client->pauses);
    }

    public function testGivesUpAfterFiveAttempts(): void {
        $client = $this->createClient()->queue(...array_fill(0, 5, new RetryableS3Exception('HTTP 500: InternalError')));

        $exception = $this->catch(static fn() => $client->deleteObject('daily/a.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertNotInstanceOf(RetryableS3Exception::class, $exception);
        self::assertSame('S3: DELETE daily/a.zip failed after 5 attempts: HTTP 500: InternalError', $exception->getMessage());
        self::assertSame([2, 4, 8, 16], $client->pauses);
        self::assertCount(5, $client->requests);
    }

    public function testLabelOfBucketRequest(): void {
        $client = $this->createClient()->queue(...array_fill(0, 5, new RetryableS3Exception('timeout')));

        $this->expectExceptionMessage('S3: GET bucket failed after 5 attempts: timeout');

        $client->listObjects('');
    }

    public function testLabelOfPartRequest(): void {
        $file = $this->createFileOfSize(5 * self::MIB + 1);
        $client = $this->createClient()->queue(
            S3ClientSpy::response('<R><UploadId>u</UploadId></R>'),
            ...array_fill(0, 5, new RetryableS3Exception('reset')),
        );
        $client->queue(S3ClientSpy::response());

        $exception = $this->catch(static fn() => $client->uploadFile($file, 'a.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('S3: PUT a.zip (part 1) failed after 5 attempts: reset', $exception->getMessage());
    }

    public function testDoesNotRetryPermanentFailures(): void {
        $client = $this->createClient()->queue(new BackupException('S3: DELETE /bucket/a.zip — HTTP 403: AccessDenied'));

        $exception = $this->catch(static fn() => $client->deleteObject('a.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('S3: DELETE /bucket/a.zip — HTTP 403: AccessDenied', $exception->getMessage());
        self::assertSame([], $client->pauses);
        self::assertCount(1, $client->requests);
    }

    public function testSignature(): void {
        $client = new S3Client($this->createConfig(endpoint: 'https://s3.example:8443'));
        $payloadHash = hash('sha256', 'payload');

        /** @var list<string> $lines */
        $lines = new ReflectionMethod(S3Client::class, 'sign')->invoke(
            $client,
            'PUT',
            '/bucket/daily/a.zip',
            'partNumber=1&uploadId=u',
            ['x-amz-copy-source' => ' /bucket/src.zip '],
            $payloadHash
        );

        self::assertCount(6, $lines);
        self::assertSame('host: s3.example:8443', $lines[0]);
        self::assertSame("x-amz-content-sha256: $payloadHash", $lines[1]);
        self::assertSame('x-amz-copy-source:  /bucket/src.zip ', $lines[2], 'The header is sent as is.');
        self::assertMatchesRegularExpression('/^x-amz-date: (\d{8})T\d{6}Z$/', $lines[3]);
        self::assertSame('Expect:', $lines[5]);

        $amzDate = substr($lines[3], strlen('x-amz-date: '));
        $date = substr($amzDate, 0, 8);
        self::assertEqualsWithDelta(time(), (int)strtotime($amzDate), 5);

        $canonicalRequest = "PUT\n/bucket/daily/a.zip\npartNumber=1&uploadId=u\n"
            . "host:s3.example:8443\nx-amz-content-sha256:$payloadHash\nx-amz-copy-source:/bucket/src.zip\nx-amz-date:$amzDate\n\n"
            . "host;x-amz-content-sha256;x-amz-copy-source;x-amz-date\n$payloadHash";
        $scope = "$date/eu-west-1/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n$amzDate\n$scope\n" . hash('sha256', $canonicalRequest);
        $key = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', 's3', hash_hmac('sha256', 'eu-west-1', hash_hmac('sha256', $date, 'AWS4secret', true), true), true), true);

        self::assertSame(
            "Authorization: AWS4-HMAC-SHA256 Credential=key-id/$scope, SignedHeaders=host;x-amz-content-sha256;x-amz-copy-source;x-amz-date, Signature="
            . hash_hmac('sha256', $stringToSign, $key),
            $lines[4]
        );
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('provideQueries')]
    public function testQueryString(array $query, string $expected): void {
        self::assertSame($expected, new ReflectionMethod(S3Client::class, 'buildQueryString')->invoke($this->createClient(), $query));
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function provideQueries(): iterable {
        yield 'empty' => [[], ''];
        yield 'sorted' => [['prefix' => 'a', 'list-type' => '2'], 'list-type=2&prefix=a'];
        yield 'encoded' => [['prefix' => 'a b/c+d~e', 'k y' => 'v'], 'k%20y=v&prefix=a%20b%2Fc%2Bd~e'];
        yield 'empty value' => [['uploads' => ''], 'uploads='];
    }

    public function testHashOfFileSlice(): void {
        $file = "$this->dir/slice.bin";
        file_put_contents($file, '0123456789');
        $hash = new ReflectionMethod(S3Client::class, 'hashFileSlice');

        self::assertSame(hash('sha256', '345'), $hash->invoke($this->createClient(), $file, 3, 3));
        self::assertSame(hash('sha256', ''), $hash->invoke($this->createClient(), $file, 0, 0));
        self::assertSame(hash('sha256', '0123456789'), $hash->invoke($this->createClient(), $file, 0, 10));
    }

    public function testHashOfSliceBeyondEndOfFile(): void {
        $file = "$this->dir/slice.bin";
        file_put_contents($file, '0123456789');

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage("S3: unexpected end of file while hashing $file");

        new ReflectionMethod(S3Client::class, 'hashFileSlice')->invoke($this->createClient(), $file, 8, 3);
    }

    public function testHashOfMissingFile(): void {
        $method = new ReflectionMethod(S3Client::class, 'hashFileSlice');
        $client = $this->createClient();

        $exception = self::quietly(fn(): ?Throwable => $this->catch(
            fn() => $method->invoke($client, "$this->dir/missing.bin", 0, 1)
        ));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame("S3: cannot read $this->dir/missing.bin", $exception->getMessage());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideErrors(): iterable {
        yield 'code and message' => ['<Error><Code>NoSuchKey</Code><Message>No such key</Message></Error>', 'NoSuchKey — No such key'];
        yield 'code only' => ['<Error><Code>NoSuchKey</Code></Error>', 'NoSuchKey'];
        yield 'XML without a code' => ['<Error><Message>Oops</Message></Error>', '<Error><Message>Oops</Message></Error>'];
        yield 'not XML' => ['Service unavailable', 'Service unavailable'];
        yield 'long' => [str_repeat('я', 400), str_repeat('я', 300)];
        yield 'empty' => ['', 'empty response'];
    }

    #[DataProvider('provideErrors')]
    public function testDescribeError(string $body, string $expected): void {
        self::assertSame($expected, new ReflectionMethod(S3Client::class, 'describeError')->invoke($this->createClient(), $body));
    }

    public function testNonXmlMessageIsShortened(): void {
        $method = new ReflectionMethod(S3Client::class, 'parseXml');
        $client = $this->createClient();

        $exception = $this->catch(static fn() => $method->invoke($client, str_repeat('я', 250)));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('S3: unexpected non-XML response: ' . str_repeat('я', 200), $exception->getMessage());
    }

    public function testFileOfExactlyTwoParts(): void {
        $file = $this->createFileOfSize(10 * self::MIB);
        $client = $this->createClient(partSizeMb: 5)->queue(
            S3ClientSpy::response('<R><UploadId>u</UploadId></R>'),
            S3ClientSpy::response('', ['etag' => 'e1']),
            S3ClientSpy::response('', ['etag' => 'e2']),
            S3ClientSpy::response('<CompleteMultipartUploadResult/>'),
        );

        $client->uploadFile($file, 'a.zip');

        self::assertSame(
            [null, [$file, 0, 5 * self::MIB], [$file, 5 * self::MIB, 5 * self::MIB], null],
            array_column($client->requests, 'fileSlice')
        );
    }

    private function createConfig(string $endpoint = 'https://s3.example', int $partSizeMb = 5): S3DestinationConfig {
        return new S3DestinationConfig(
            name: 'S3',
            endpoint: $endpoint,
            bucket: 'bucket',
            accessKeyId: 'key-id',
            secretAccessKey: 'secret',
            region: 'eu-west-1',
            partSizeMb: $partSizeMb
        );
    }

    private function createClient(int $partSizeMb = 5): S3ClientSpy {
        return new S3ClientSpy($this->createConfig(partSizeMb: $partSizeMb));
    }

    private function createFile(string $content, int $repeat): string {
        $path = "$this->dir/archive-" . bin2hex(random_bytes(4)) . '.zip';
        file_put_contents($path, str_repeat($content, $repeat));

        return $path;
    }

    private function createFileOfSize(int $size): string {
        return $this->createFile('x', $size);
    }

    private function catch(callable $callback): ?Throwable {
        try {
            $callback();
        } catch (Throwable $e) {
            return $e;
        }

        return null;
    }
}
