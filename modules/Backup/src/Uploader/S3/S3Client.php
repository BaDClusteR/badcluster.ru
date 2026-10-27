<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\S3;

use BC\Core\Trait\LoggerTrait;
use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Exception\BackupException;
use SimpleXMLElement;
use Throwable;

/**
 * Minimal S3 REST client (AWS Signature V4) — ext-curl + SimpleXML only, no Composer
 * dependencies. Uses path-style URLs (endpoint/bucket/key), which every S3-compatible
 * storage understands, Cloudflare R2 included.
 *
 * Files larger than one part go up as a multipart upload. Every request — each part
 * included — is retried on its own on network errors and 5xx/429 responses, so a dropped
 * connection mid-transfer costs one part, not the whole archive. Retries that eventually
 * succeed are logged as warnings. All methods throw BackupException on failure.
 */
class S3Client {
    use LoggerTrait;

    private const int MIN_PART_SIZE = 5 * 1024 * 1024;
    private const int MAX_ATTEMPTS = 5;
    private const int HASH_CHUNK_SIZE = 1024 * 1024;

    private readonly string $baseUrl;
    private readonly string $host;

    public function __construct(
        private readonly S3DestinationConfig $config,
    ) {
        $parts = parse_url($config->endpoint);

        if (empty($parts['host'])) {
            throw new BackupException('S3: BACKUP_S3_ENDPOINT is not a valid URL: ' . $config->endpoint);
        }

        $this->host = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $this->baseUrl = ($parts['scheme'] ?? 'https') . '://' . $this->host;
    }

    /**
     * Uploads a local file under $key: a single PUT when it fits in one part,
     * a multipart upload otherwise.
     */
    public function uploadFile(string $localFile, string $key): void {
        $size = filesize($localFile);
        if ($size === false) {
            throw new BackupException('S3: cannot stat ' . $localFile);
        }

        $partSize = max(self::MIN_PART_SIZE, $this->config->partSizeMb * 1024 * 1024);

        if ($size <= $partSize) {
            $this->request('PUT', $key, fileSlice: [$localFile, 0, $size]);

            return;
        }

        $uploadId = $this->createMultipartUpload($key);

        try {
            $etags = [];

            for ($offset = 0, $partNumber = 1; $offset < $size; $offset += $partSize, $partNumber++) {
                $response = $this->request(
                    'PUT',
                    $key,
                    ['partNumber' => (string)$partNumber, 'uploadId' => $uploadId],
                    fileSlice: [$localFile, $offset, min($partSize, $size - $offset)]
                );

                if (empty($response['headers']['etag'])) {
                    throw new BackupException('S3: part ' . $partNumber . ' was accepted without an ETag.');
                }

                $etags[$partNumber] = $response['headers']['etag'];
            }

            $this->completeMultipartUpload($key, $uploadId, $etags);
        } catch (Throwable $e) {
            // Uploaded parts are billed storage until the upload is aborted.
            try {
                $this->request('DELETE', $key, ['uploadId' => $uploadId]);
            } catch (Throwable) {
            }

            throw $e;
        }
    }

    /** Server-side copy of an object within the bucket — no data goes over our link. */
    public function copyObject(string $sourceKey, string $key): void {
        $response = $this->request('PUT', $key, headers: [
            'x-amz-copy-source' => '/' . $this->config->bucket . '/' . $this->encodeKey($sourceKey),
        ]);

        // A copy can fail after the 200 status line has already been sent.
        $this->assertNoErrorInBody($response['body'], 'copy to ' . $key);
    }

    /**
     * Lists objects under a prefix (all pages).
     *
     * @return array<array{key: string, lastModified: string}>
     */
    public function listObjects(string $prefix): array {
        $objects = [];
        $continuationToken = null;

        do {
            $query = ['list-type' => '2', 'prefix' => $prefix];
            if ($continuationToken !== null) {
                $query['continuation-token'] = $continuationToken;
            }

            $xml = $this->parseXml($this->request('GET', '', $query)['body']);

            foreach ($xml->Contents as $object) {
                $objects[] = [
                    'key'          => (string)$object->Key,
                    'lastModified' => (string)$object->LastModified,
                ];
            }

            $continuationToken = (string)$xml->IsTruncated === 'true'
                ? (string)$xml->NextContinuationToken
                : null;
        } while ($continuationToken);

        return $objects;
    }

    public function deleteObject(string $key): void {
        $this->request('DELETE', $key);
    }

    private function createMultipartUpload(string $key): string {
        $xml = $this->parseXml($this->request('POST', $key, ['uploads' => ''])['body']);
        $uploadId = (string)$xml->UploadId;

        if ($uploadId === '') {
            throw new BackupException('S3: multipart upload was created without an UploadId.');
        }

        return $uploadId;
    }

    /**
     * @param array<int, string> $etags part number => ETag
     */
    private function completeMultipartUpload(string $key, string $uploadId, array $etags): void {
        $body = '<CompleteMultipartUpload>';
        foreach ($etags as $partNumber => $etag) {
            $body .= '<Part><PartNumber>' . $partNumber . '</PartNumber>'
                . '<ETag>' . htmlspecialchars($etag, ENT_XML1) . '</ETag></Part>';
        }
        $body .= '</CompleteMultipartUpload>';

        $response = $this->request('POST', $key, ['uploadId' => $uploadId], $body);

        // Like a copy, completion may report an error inside a 200 response.
        $this->assertNoErrorInBody($response['body'], 'completing upload of ' . $key);
    }

    /**
     * Signed request with retries. The body is either a string or a slice of a local file
     * [path, offset, length], streamed from disk.
     *
     * @param array<string, string>                $query
     * @param array{0: string, 1: int, 2: int}|null $fileSlice
     * @param array<string, string>                $headers extra headers, lowercase names; all are signed
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function request(
        string $method,
        string $key,
        array $query = [],
        string $body = '',
        ?array $fileSlice = null,
        array $headers = [],
    ): array {
        $path = '/' . $this->config->bucket . ($key !== '' ? '/' . $this->encodeKey($key) : '');
        $payloadHash = $fileSlice !== null
            ? $this->hashFileSlice(...$fileSlice)
            : hash('sha256', $body);
        $label = $method . ' ' . ($key !== '' ? $key : $this->config->bucket)
            . (isset($query['partNumber']) ? ' (part ' . $query['partNumber'] . ')' : '');

        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->send($method, $path, $query, $body, $fileSlice, $headers, $payloadHash);
            } catch (RetryableS3Exception $e) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw new BackupException(
                        'S3: ' . $label . ' failed after ' . $attempt . ' attempts: ' . $e->getMessage()
                    );
                }

                $delay = 2 ** $attempt;
                $this->getLogger()->warning(sprintf(
                    '[Backup] S3: %s, attempt %d of %d failed (%s), retrying in %d s',
                    $label,
                    $attempt,
                    self::MAX_ATTEMPTS,
                    $e->getMessage(),
                    $delay
                ));

                $this->pause($delay);
            }
        }
    }

    /** The back-off between the attempts of a request. */
    protected function pause(int $seconds): void {
        sleep($seconds);
    }

    /**
     * @param array<string, string>                $query
     * @param array{0: string, 1: int, 2: int}|null $fileSlice
     * @param array<string, string>                $headers
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    protected function send(
        string $method,
        string $path,
        array $query,
        string $body,
        ?array $fileSlice,
        array $headers,
        string $payloadHash,
    ): array {
        $queryString = $this->buildQueryString($query);
        $curl = curl_init();
        $handle = null;
        $responseHeaders = [];

        $options = [
            CURLOPT_URL            => $this->baseUrl . $path . ($queryString !== '' ? '?' . $queryString : ''),
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $this->sign($method, $path, $queryString, $headers, $payloadHash),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            // Abort only on stall, not on duration — big uploads on slow links take a while.
            CURLOPT_LOW_SPEED_LIMIT => 1024,
            CURLOPT_LOW_SPEED_TIME  => 60,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($value);
                }

                return strlen($line);
            },
        ];

        if ($fileSlice !== null) {
            [$file, $offset, $length] = $fileSlice;

            $handle = fopen($file, 'rb');
            if ($handle === false || fseek($handle, $offset) !== 0) {
                throw new BackupException('S3: cannot read ' . $file);
            }

            // Hand curl exactly $length bytes from $offset, not the rest of the file.
            $remaining = $length;
            $options += [
                CURLOPT_UPLOAD       => true,
                CURLOPT_INFILE       => $handle,
                CURLOPT_INFILESIZE   => $length,
                CURLOPT_READFUNCTION => static function ($curl, $stream, int $maxLength) use ($handle, &$remaining): string {
                    if ($remaining <= 0) {
                        return '';
                    }

                    $chunk = fread($handle, min($maxLength, $remaining));
                    if ($chunk === false) {
                        return '';
                    }

                    $remaining -= strlen($chunk);

                    return $chunk;
                },
            ];
        } elseif ($method === 'PUT' || $method === 'POST') {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($curl, $options);

        try {
            $response = curl_exec($curl);

            if ($response === false) {
                throw new RetryableS3Exception('curl error: ' . curl_error($curl));
            }

            $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        } finally {
            if ($handle !== null) {
                fclose($handle);
            }
        }

        $response = (string)$response;

        if ($status >= 400) {
            $message = 'HTTP ' . $status . ': ' . $this->describeError($response);

            if ($status >= 500 || $status === 429 || str_contains($response, '<Code>RequestTimeout</Code>')) {
                throw new RetryableS3Exception($message);
            }

            throw new BackupException('S3: ' . $method . ' ' . $path . ' — ' . $message);
        }

        return ['status' => $status, 'headers' => $responseHeaders, 'body' => $response];
    }

    /**
     * AWS Signature V4 for the s3 service. Returns the full header list for curl.
     *
     * @param array<string, string> $headers
     *
     * @return string[]
     */
    private function sign(string $method, string $path, string $queryString, array $headers, string $payloadHash): array {
        $amzDate = gmdate('Ymd\THis\Z');
        $date = substr($amzDate, 0, 8);

        $headers['host'] = $this->host;
        $headers['x-amz-date'] = $amzDate;
        $headers['x-amz-content-sha256'] = $payloadHash;
        ksort($headers);

        $canonicalHeaders = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim($value) . "\n";
        }

        $signedHeaders = implode(';', array_keys($headers));
        $scope = $date . '/' . $this->config->region . '/s3/aws4_request';

        $canonicalRequest = implode("\n", [
            $method,
            $path,
            $queryString,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = 'AWS4' . $this->config->secretAccessKey;
        foreach ([$date, $this->config->region, 's3', 'aws4_request'] as $part) {
            $signingKey = hash_hmac('sha256', $part, $signingKey, true);
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        $lines[] = 'Authorization: AWS4-HMAC-SHA256 Credential=' . $this->config->accessKeyId . '/' . $scope
            . ', SignedHeaders=' . $signedHeaders
            . ', Signature=' . hash_hmac('sha256', $stringToSign, $signingKey);

        // curl adds "Expect: 100-continue" to big uploads; some S3 implementations stall on it.
        $lines[] = 'Expect:';

        return $lines;
    }

    /**
     * Canonical (sorted, RFC 3986-encoded) query string; used both for signing and in the URL,
     * so the two can never disagree.
     *
     * @param array<string, string> $query
     */
    private function buildQueryString(array $query): string {
        ksort($query);

        $pairs = [];
        foreach ($query as $name => $value) {
            $pairs[] = rawurlencode((string)$name) . '=' . rawurlencode($value);
        }

        return implode('&', $pairs);
    }

    private function encodeKey(string $key): string {
        return implode('/', array_map('rawurlencode', explode('/', $key)));
    }

    private function hashFileSlice(string $file, int $offset, int $length): string {
        $handle = fopen($file, 'rb');
        if ($handle === false || fseek($handle, $offset) !== 0) {
            throw new BackupException('S3: cannot read ' . $file);
        }

        try {
            $context = hash_init('sha256');
            $remaining = $length;

            while ($remaining > 0) {
                $chunk = fread($handle, min(self::HASH_CHUNK_SIZE, $remaining));
                if ($chunk === false || $chunk === '') {
                    throw new BackupException('S3: unexpected end of file while hashing ' . $file);
                }

                hash_update($context, $chunk);
                $remaining -= strlen($chunk);
            }

            return hash_final($context);
        } finally {
            fclose($handle);
        }
    }

    private function assertNoErrorInBody(string $body, string $action): void {
        if (str_contains($body, '<Error>')) {
            throw new BackupException('S3: ' . $action . ' failed: ' . $this->describeError($body));
        }
    }

    private function describeError(string $body): string {
        $xml = @simplexml_load_string($body);

        if ($xml !== false && isset($xml->Code)) {
            return trim($xml->Code . ' — ' . $xml->Message, ' —');
        }

        return $body !== '' ? mb_substr($body, 0, 300) : 'empty response';
    }

    private function parseXml(string $body): SimpleXMLElement {
        $xml = @simplexml_load_string($body);

        if ($xml === false) {
            throw new BackupException('S3: unexpected non-XML response: ' . mb_substr($body, 0, 200));
        }

        return $xml;
    }
}
