<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader\GoogleDrive;

use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Uploader\GoogleDrive\GoogleDriveClient;
use BC\Tests\Fixture\Backup\GoogleDriveClientSpy;
use BC\Tests\Fixture\Backup\Quietly;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Everything but the wire: the requests go to GoogleDriveClientSpy.
 */
#[CoversClass(GoogleDriveClient::class)]
final class GoogleDriveClientTest extends TestCase {
    use Quietly;

    private const string FILES_URL = 'https://www.googleapis.com/drive/v3/files';

    private string $dir;

    protected function setUp(): void {
        $this->dir = TempDir::create();
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    public function testRefreshTokenFlow(): void {
        $client = $this->createClient(['client_id' => 'cid', 'client_secret' => 'csecret', 'refresh_token' => 'rtoken'])
            ->queue('{"access_token": "token-1"}')
            ->queue('{"id": "folder-1"}')
            ->queue('{"id": "folder-2"}');

        self::assertSame('folder-1', $client->createFolder('parent', 'daily'));
        self::assertSame('folder-2', $client->createFolder('parent', 'weekly'));

        self::assertCount(3, $client->requests, 'The token is requested once.');
        self::assertSame('https://oauth2.googleapis.com/token', $client->requests[0]['url']);
        self::assertTrue($client->requests[0]['skipAuthHeader']);
        self::assertSame(
            [
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS    => 'grant_type=refresh_token&client_id=cid&client_secret=csecret&refresh_token=rtoken',
            ],
            $client->requests[0]['options']
        );
        self::assertSame(
            ['Authorization: Bearer token-1', 'Content-Type: application/json; charset=UTF-8'],
            $client->requests[1]['options'][CURLOPT_HTTPHEADER]
        );
    }

    public function testTokenUriOfKeyFile(): void {
        $client = $this->createClient([
            'client_id'     => 'cid',
            'client_secret' => 'csecret',
            'refresh_token' => 'rtoken',
            'token_uri'     => 'https://token.example/token',
        ])->queue('{"access_token": "t"}')->queue('{"id": "f"}');

        $client->createFolder('parent', 'daily');

        self::assertSame('https://token.example/token', $client->requests[0]['url']);
    }

    public function testServiceAccountJwt(): void {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        self::assertTrue(openssl_pkey_export($key, $privateKey));
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        $client = $this->createClient([
            'type'         => 'service_account',
            'client_email' => 'backup@project.iam.example',
            'private_key'  => $privateKey,
            'token_uri'    => 'https://oauth2.example/token',
        ])->queue('{"access_token": "t"}')->queue('{"id": "f"}');

        $client->createFolder('parent', 'daily');

        self::assertSame('https://oauth2.example/token', $client->requests[0]['url']);
        $postFields = $client->requests[0]['options'][CURLOPT_POSTFIELDS];
        self::assertIsString($postFields);
        parse_str($postFields, $form);
        self::assertSame('urn:ietf:params:oauth:grant-type:jwt-bearer', $form['grant_type'] ?? null);
        self::assertIsString($form['assertion'] ?? null);

        $segments = explode('.', $form['assertion']);
        self::assertCount(3, $segments);
        self::assertDoesNotMatchRegularExpression('/[+\/=]/', $form['assertion'], 'Base64url without the padding.');
        self::assertSame(['alg' => 'RS256', 'typ' => 'JWT'], $this->decodeSegment($segments[0]));

        $claims = $this->decodeSegment($segments[1]);
        self::assertSame('backup@project.iam.example', $claims['iss'] ?? null);
        self::assertSame('https://www.googleapis.com/auth/drive', $claims['scope'] ?? null);
        self::assertSame('https://oauth2.example/token', $claims['aud'] ?? null);
        self::assertIsInt($claims['iat'] ?? null);
        self::assertEqualsWithDelta(time(), $claims['iat'], 5);
        self::assertSame($claims['iat'] + 3600, $claims['exp'] ?? null);

        $signature = base64_decode(strtr($segments[2], '-_', '+/'), true);
        self::assertIsString($signature);
        self::assertIsString($details['key'] ?? null);
        self::assertSame(1, openssl_verify("$segments[0].$segments[1]", $signature, $details['key'], OPENSSL_ALGO_SHA256));
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function provideInvalidKeys(): iterable {
        $serviceAccount = ['type' => 'service_account', 'client_email' => 'e', 'private_key' => 'k', 'token_uri' => 'u'];

        foreach (['client_email', 'private_key', 'token_uri'] as $field) {
            yield "service account without $field" => [
                [...$serviceAccount, $field => ''],
                "Google Drive: service-account key file lacks \"$field\".",
            ];
        }

        $token = ['client_id' => 'i', 'client_secret' => 's', 'refresh_token' => 'r'];

        foreach (['client_id', 'client_secret', 'refresh_token'] as $field) {
            $key = $token;
            unset($key[$field]);

            yield "token file without $field" => [
                $key,
                'Google Drive: key file is neither a service-account key nor an OAuth token file (missing "' . $field
                . '"). Run `./console backup:gdrive-auth` to create one.',
            ];
        }
    }

    /**
     * @param array<string, string> $key
     */
    #[DataProvider('provideInvalidKeys')]
    public function testInvalidKeyFile(array $key, string $error): void {
        $client = $this->createClient($key);

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage($error);

        $client->createFolder('parent', 'daily');
    }

    public function testBadPrivateKey(): void {
        $client = $this->createClient([
            'type'         => 'service_account',
            'client_email' => 'e',
            'private_key'  => 'not a key',
            'token_uri'    => 'https://oauth2.example/token',
        ]);

        $exception = self::quietly(fn(): ?Throwable => $this->catch(static fn() => $client->createFolder('parent', 'daily')));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame('Google Drive: cannot sign the auth JWT (bad private key?).', $exception->getMessage());
        self::assertSame([], $client->requests);
    }

    public function testUnreadableKeyFile(): void {
        $client = new GoogleDriveClientSpy("$this->dir/missing.json");

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage("Google Drive: key file is not readable: $this->dir/missing.json");

        $client->createFolder('parent', 'daily');
    }

    public function testNonJsonKeyFile(): void {
        file_put_contents("$this->dir/key.json", 'not json, but a secret');
        $client = new GoogleDriveClientSpy("$this->dir/key.json");

        // The content of the key file is not in the message.
        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('Google Drive: unexpected non-JSON data (22 bytes).');

        $client->createFolder('parent', 'daily');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTokenResponsesWithoutToken(): iterable {
        yield 'no token' => ['{"error": "invalid_grant"}'];
        yield 'empty token' => ['{"access_token": ""}'];
        yield 'not a string' => ['{"access_token": 42}'];
    }

    #[DataProvider('provideTokenResponsesWithoutToken')]
    public function testTokenResponseWithoutToken(string $response): void {
        $client = $this->createTokenClient()->queue($response);

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('Google Drive: token endpoint returned no access_token.');

        $client->createFolder('parent', 'daily');
    }

    public function testFindChildFolder(): void {
        $client = $this->createTokenClient()->queue('{"files": [{"id": "folder-1"}, {"id": "folder-2"}]}');

        self::assertSame('folder-1', $client->findChildFolder('parent-id', "Bob's"));

        self::assertSame(
            self::FILES_URL . '?' . http_build_query([
                'q'                         => "'parent-id' in parents and name = 'Bob\\'s' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
                'fields'                    => 'files(id)',
                'pageSize'                  => 1,
                'supportsAllDrives'         => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]),
            $client->requests[0]['url']
        );
        self::assertSame([CURLOPT_CUSTOMREQUEST => 'GET'], $client->requests[0]['options']);
        self::assertFalse($client->requests[0]['skipAuthHeader']);
    }

    public function testChildFolderNotFound(): void {
        $client = $this->createTokenClient()->queue('{"files": []}')->queue('{}');

        self::assertNull($client->findChildFolder('parent-id', 'daily'));
        self::assertNull($client->findChildFolder('parent-id', 'daily'));
    }

    public function testCreateFolder(): void {
        $client = $this->createTokenClient()->queue('{"access_token": "t"}')->queue('{"id": "new-folder"}');

        self::assertSame('new-folder', $client->createFolder('parent-id', 'monthly'));

        $request = $client->requests[1];
        self::assertSame(self::FILES_URL . '?supportsAllDrives=true&fields=id', $request['url']);
        self::assertSame('POST', $request['options'][CURLOPT_CUSTOMREQUEST]);
        self::assertSame(
            '{"name":"monthly","mimeType":"application\/vnd.google-apps.folder","parents":["parent-id"]}',
            $request['options'][CURLOPT_POSTFIELDS]
        );
    }

    public function testCreateFolderWithoutId(): void {
        $client = $this->createTokenClient()->queue('{"access_token": "t"}')->queue('{"name": "monthly"}');

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('Google Drive: folder creation returned no id.');

        $client->createFolder('parent-id', 'monthly');
    }

    public function testUploadFile(): void {
        $file = "$this->dir/archive.zip";
        file_put_contents($file, '123456');
        $client = $this->createTokenClient()
            ->queue('{"access_token": "t"}')
            ->queue('', ['HTTP/1.1 200 OK', 'Location : https://upload.example/session?id=1', 'X-Other: x'])
            ->queue('{"id": "file-1", "webViewLink": "https://drive.example/file-1"}');

        self::assertSame(
            ['id' => 'file-1', 'webViewLink' => 'https://drive.example/file-1'],
            $client->uploadFile($file, 'folder-1', 'archive.zip')
        );

        self::assertCount(3, $client->requests);
        [, $session, $upload] = $client->requests;

        self::assertSame(
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true&fields=id%2C+webViewLink',
            $session['url']
        );
        self::assertTrue($session['skipAuthHeader']);
        self::assertSame('POST', $session['options'][CURLOPT_CUSTOMREQUEST]);
        self::assertSame('{"name":"archive.zip","parents":["folder-1"]}', $session['options'][CURLOPT_POSTFIELDS]);
        self::assertSame(
            ['Authorization: Bearer t', 'Content-Type: application/json; charset=UTF-8', 'X-Upload-Content-Length: 6'],
            $session['options'][CURLOPT_HTTPHEADER]
        );

        self::assertSame('https://upload.example/session?id=1', $upload['url']);
        self::assertFalse($upload['skipAuthHeader']);
        self::assertSame('PUT', $upload['options'][CURLOPT_CUSTOMREQUEST]);
        self::assertTrue($upload['options'][CURLOPT_UPLOAD]);
        self::assertSame(6, $upload['options'][CURLOPT_INFILESIZE]);
        self::assertSame(1024, $upload['options'][CURLOPT_LOW_SPEED_LIMIT]);
        self::assertSame(60, $upload['options'][CURLOPT_LOW_SPEED_TIME]);
        self::assertFalse(is_resource($upload['options'][CURLOPT_INFILE]), 'The file is closed after the upload.');
    }

    public function testUploadWithoutSessionUrl(): void {
        $file = "$this->dir/archive.zip";
        file_put_contents($file, '1');
        $client = $this->createTokenClient()->queue('{"access_token": "t"}')->queue('', ['X-Other: x']);

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('Google Drive: resumable session gave no upload URL.');

        $client->uploadFile($file, 'folder-1', 'archive.zip');
    }

    public function testUploadWithoutFileId(): void {
        $file = "$this->dir/archive.zip";
        file_put_contents($file, '1');
        $client = $this->createTokenClient()
            ->queue('{"access_token": "t"}')
            ->queue('', ['location: https://upload.example/session'])
            ->queue('{"name": "archive.zip"}');

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('Google Drive: upload finished but no file id was returned.');

        $client->uploadFile($file, 'folder-1', 'archive.zip');
    }

    public function testUploadOfMissingFile(): void {
        $client = $this->createTokenClient();

        $exception = self::quietly(fn(): ?Throwable => $this->catch(
            fn() => $client->uploadFile("$this->dir/missing.zip", 'folder-1', 'missing.zip')
        ));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame("Google Drive: cannot stat $this->dir/missing.zip", $exception->getMessage());
        self::assertSame([], $client->requests);
    }

    public function testFailedUploadClosesFile(): void {
        $file = "$this->dir/archive.zip";
        file_put_contents($file, '1');
        $client = $this->createTokenClient()
            ->queue('{"access_token": "t"}')
            ->queue('', ['Location: https://upload.example/session'])
            ->queueException(new BackupException('Google Drive: HTTP 500'));

        $exception = $this->catch(static fn() => $client->uploadFile($file, 'folder-1', 'archive.zip'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertFalse(is_resource($client->requests[2]['options'][CURLOPT_INFILE]));
    }

    public function testListFiles(): void {
        $files = [['id' => 'f2', 'name' => 'b.zip', 'createdTime' => '2026-07-02'], ['id' => 'f1', 'name' => 'a.zip', 'createdTime' => '2026-07-01']];
        $client = $this->createTokenClient()->queue((string)json_encode(['files' => $files]))->queue('{}');

        self::assertSame($files, $client->listFiles('folder-1'));
        self::assertSame([], $client->listFiles('folder-1'));

        self::assertSame(
            self::FILES_URL . '?' . http_build_query([
                'q'                         => "'folder-1' in parents and trashed = false",
                'orderBy'                   => 'createdTime desc',
                'fields'                    => 'files(id, name, createdTime)',
                'pageSize'                  => 100,
                'supportsAllDrives'         => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]),
            $client->requests[0]['url']
        );
    }

    public function testDeleteFile(): void {
        $client = $this->createTokenClient()->queue('');

        $client->deleteFile('file/1');

        self::assertSame(self::FILES_URL . '/file%2F1?supportsAllDrives=true', $client->requests[0]['url']);
        self::assertSame([CURLOPT_CUSTOMREQUEST => 'DELETE'], $client->requests[0]['options']);
    }

    public function testNonJsonResponse(): void {
        $client = $this->createTokenClient()->queue(str_repeat('Ошибка', 50));

        $exception = $this->catch(static fn() => $client->listFiles('folder-1'));

        self::assertInstanceOf(BackupException::class, $exception);
        self::assertSame(
            'Google Drive: unexpected non-JSON data (' . strlen(str_repeat('Ошибка', 50)) . ' bytes).',
            $exception->getMessage()
        );
    }

    /**
     * @param array<string, mixed> $key
     */
    private function createClient(array $key): GoogleDriveClientSpy {
        $path = "$this->dir/key-" . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($path, (string)json_encode($key));

        return new GoogleDriveClientSpy($path);
    }

    private function createTokenClient(): GoogleDriveClientSpy {
        return $this->createClient(['client_id' => 'cid', 'client_secret' => 'csecret', 'refresh_token' => 'rtoken']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSegment(string $segment): array {
        $json = base64_decode(strtr($segment, '-_', '+/'), true);
        self::assertIsString($json);
        $data = json_decode($json, true);
        self::assertIsArray($data);

        /** @var array<string, mixed> $data */
        return $data;
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
