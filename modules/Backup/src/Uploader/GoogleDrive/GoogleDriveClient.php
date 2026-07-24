<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\GoogleDrive;

use BC\Modules\Backup\Exception\BackupException;

/**
 * Minimal Google Drive v3 REST client — ext-curl + openssl only, no Composer dependencies.
 *
 * Two key-file formats are supported (detected by content):
 *  - a service-account JSON (type=service_account): JWT flow. NOTE: Google no longer grants
 *    service accounts storage quota, so this only works for Shared Drives (Workspace).
 *  - an OAuth token JSON {client_id, client_secret, refresh_token}: refresh-token flow,
 *    acting as the user who authorised via `./console backup:gdrive-auth`. Files are owned
 *    by that user and consume their quota. This is the path for personal accounts.
 *
 * The access token is cached for the instance lifetime; a backup run finishes well within
 * its one-hour validity. All methods throw BackupException on transport/API errors.
 */
class GoogleDriveClient {
    private const string SCOPE = 'https://www.googleapis.com/auth/drive';
    private const string API_BASE = 'https://www.googleapis.com/drive/v3';
    private const string UPLOAD_BASE = 'https://www.googleapis.com/upload/drive/v3';
    private const string FOLDER_MIME = 'application/vnd.google-apps.folder';

    private ?string $accessToken = null;

    public function __construct(
        private readonly string $keyFilePath,
    ) {}

    /** Returns the ID of the named sub-folder of $parentId, or null when it doesn't exist. */
    public function findChildFolder(string $parentId, string $name): ?string {
        $query = sprintf(
            "'%s' in parents and name = '%s' and mimeType = '%s' and trashed = false",
            $parentId,
            str_replace("'", "\\'", $name),
            self::FOLDER_MIME
        );

        $response = $this->request('GET', self::API_BASE . '/files?' . http_build_query([
            'q'                        => $query,
            'fields'                   => 'files(id)',
            'pageSize'                 => 1,
            'supportsAllDrives'        => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]));

        return $response['files'][0]['id'] ?? null;
    }

    /** Creates a sub-folder and returns its ID. */
    public function createFolder(string $parentId, string $name): string {
        $response = $this->request(
            'POST',
            self::API_BASE . '/files?' . http_build_query(['supportsAllDrives' => 'true', 'fields' => 'id']),
            [
                'name'     => $name,
                'mimeType' => self::FOLDER_MIME,
                'parents'  => [$parentId],
            ]
        );

        if (empty($response['id'])) {
            throw new BackupException('Google Drive: folder creation returned no id.');
        }

        return $response['id'];
    }

    /**
     * Uploads a local file via a resumable session (single PUT — libcurl streams from disk).
     *
     * @return array{id: string, webViewLink?: string}
     */
    public function uploadFile(string $localFile, string $parentId, string $name): array {
        $size = filesize($localFile);
        if ($size === false) {
            throw new BackupException('Google Drive: cannot stat ' . $localFile);
        }

        $sessionUrl = $this->initiateResumableSession($parentId, $name, $size);

        $handle = fopen($localFile, 'rb');
        if ($handle === false) {
            throw new BackupException('Google Drive: cannot open ' . $localFile . ' for reading.');
        }

        try {
            $response = $this->rawRequest($sessionUrl, [
                CURLOPT_CUSTOMREQUEST   => 'PUT',
                CURLOPT_UPLOAD          => true,
                CURLOPT_INFILE          => $handle,
                CURLOPT_INFILESIZE      => $size,
                // Abort only on stall, not on duration — big uploads on slow links take hours.
                CURLOPT_LOW_SPEED_LIMIT => 1024,
                CURLOPT_LOW_SPEED_TIME  => 60,
            ]);
        } finally {
            fclose($handle);
        }

        $file = $this->decodeJson($response);

        if (empty($file['id'])) {
            throw new BackupException('Google Drive: upload finished but no file id was returned.');
        }

        return $file;
    }

    /**
     * Lists non-trashed files in a folder, newest (by createdTime) first.
     *
     * @return array<array{id: string, name: string, createdTime: string}>
     */
    public function listFiles(string $parentId): array {
        $response = $this->request('GET', self::API_BASE . '/files?' . http_build_query([
            'q'                        => sprintf("'%s' in parents and trashed = false", $parentId),
            'orderBy'                  => 'createdTime desc',
            'fields'                   => 'files(id, name, createdTime)',
            'pageSize'                 => 100,
            'supportsAllDrives'        => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]));

        return $response['files'] ?? [];
    }

    /** Permanently deletes a file (service-account-owned files bypass the trash). */
    public function deleteFile(string $fileId): void {
        $this->request(
            'DELETE',
            self::API_BASE . '/files/' . urlencode($fileId) . '?supportsAllDrives=true'
        );
    }

    private function initiateResumableSession(string $parentId, string $name, int $size): string {
        $url = self::UPLOAD_BASE . '/files?' . http_build_query([
            'uploadType'        => 'resumable',
            'supportsAllDrives' => 'true',
            'fields'            => 'id, webViewLink',
        ]);

        $body = json_encode(['name' => $name, 'parents' => [$parentId]], JSON_THROW_ON_ERROR);

        $headers = [];
        $this->rawRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS    => $body,
            CURLOPT_HTTPHEADER    => [
                'Authorization: Bearer ' . $this->getAccessToken(),
                'Content-Type: application/json; charset=UTF-8',
                'X-Upload-Content-Length: ' . $size,
            ],
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($key))] = trim($value);
                }

                return strlen($line);
            },
        ], skipAuthHeader: true);

        if (empty($headers['location'])) {
            throw new BackupException('Google Drive: resumable session gave no upload URL.');
        }

        return $headers['location'];
    }

    /**
     * JSON request against the Drive API with Bearer auth.
     */
    private function request(string $method, string $url, ?array $jsonBody = null): array {
        $options = [CURLOPT_CUSTOMREQUEST => $method];

        if ($jsonBody !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($jsonBody, JSON_THROW_ON_ERROR);
            $options[CURLOPT_HTTPHEADER] = [
                'Authorization: Bearer ' . $this->getAccessToken(),
                'Content-Type: application/json; charset=UTF-8',
            ];
        }

        $response = $this->rawRequest($url, $options);

        // DELETE returns an empty body on success.
        return $response === '' ? [] : $this->decodeJson($response);
    }

    /**
     * Low-level curl call. Adds the Bearer header unless the caller supplied its own
     * CURLOPT_HTTPHEADER (or asked to skip). Throws on curl errors and HTTP >= 400.
     */
    private function rawRequest(string $url, array $options, bool $skipAuthHeader = false): string {
        $curl = curl_init();

        if (!isset($options[CURLOPT_HTTPHEADER]) && !$skipAuthHeader) {
            $options[CURLOPT_HTTPHEADER] = ['Authorization: Bearer ' . $this->getAccessToken()];
        }

        curl_setopt_array($curl, $options + [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            throw new BackupException('Google Drive: curl error: ' . curl_error($curl));
        }

        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($status >= 400) {
            throw new BackupException(
                'Google Drive: HTTP ' . $status . ' for ' . strtok($url, '?') . ': '
                . mb_substr((string)$response, 0, 500)
            );
        }

        return (string)$response;
    }

    private function getAccessToken(): string {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $key = $this->readKeyFile();

        $tokenRequest = ($key['type'] ?? '') === 'service_account'
            ? $this->buildServiceAccountTokenRequest($key)
            : $this->buildRefreshTokenRequest($key);

        $response = $this->rawRequest($key['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS    => http_build_query($tokenRequest),
        ], skipAuthHeader: true);

        $token = $this->decodeJson($response)['access_token'] ?? null;

        if (!is_string($token) || $token === '') {
            throw new BackupException('Google Drive: token endpoint returned no access_token.');
        }

        return $this->accessToken = $token;
    }

    /**
     * @return array<string, string>
     */
    private function buildServiceAccountTokenRequest(array $key): array {
        foreach (['client_email', 'private_key', 'token_uri'] as $field) {
            if (empty($key[$field])) {
                throw new BackupException('Google Drive: service-account key file lacks "' . $field . '".');
            }
        }

        $now = time();

        return [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $this->buildJwt(
                [
                    'iss'   => $key['client_email'],
                    'scope' => self::SCOPE,
                    'aud'   => $key['token_uri'],
                    'iat'   => $now,
                    'exp'   => $now + 3600,
                ],
                $key['private_key']
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildRefreshTokenRequest(array $key): array {
        foreach (['client_id', 'client_secret', 'refresh_token'] as $field) {
            if (empty($key[$field])) {
                throw new BackupException(
                    'Google Drive: key file is neither a service-account key nor an OAuth token file '
                    . '(missing "' . $field . '"). Run `./console backup:gdrive-auth` to create one.'
                );
            }
        }

        return [
            'grant_type'    => 'refresh_token',
            'client_id'     => $key['client_id'],
            'client_secret' => $key['client_secret'],
            'refresh_token' => $key['refresh_token'],
        ];
    }

    private function readKeyFile(): array {
        if (!is_readable($this->keyFilePath)) {
            throw new BackupException('Google Drive: key file is not readable: ' . $this->keyFilePath);
        }

        return $this->decodeJson((string)file_get_contents($this->keyFilePath));
    }

    private function buildJwt(array $claims, string $privateKey): string {
        $segments = [
            $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        $signed = openssl_sign(implode('.', $segments), $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            throw new BackupException('Google Drive: cannot sign the auth JWT (bad private key?).');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function decodeJson(string $json): array {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new BackupException('Google Drive: unexpected non-JSON response: ' . mb_substr($json, 0, 200));
        }

        return $data;
    }
}
