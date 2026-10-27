<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Uploader\S3\S3Client;
use LogicException;
use Throwable;

/**
 * Records the requests instead of sending them: the responses (or the exceptions) are given by the test, in order.
 * The back-off of the retries is recorded, not waited for.
 */
final class S3ClientSpy extends S3Client {
    /**
     * @var list<array{method: string, path: string, query: array<string, string>, body: string,
     *     fileSlice: array{0: string, 1: int, 2: int}|null, headers: array<string, string>, payloadHash: string}>
     */
    public array $requests = [];

    /** @var list<int> */
    public array $pauses = [];

    /** @var list<array{status: int, headers: array<string, string>, body: string}|Throwable> */
    public array $responses = [];

    /**
     * @param array{status: int, headers: array<string, string>, body: string}|Throwable ...$responses
     */
    public function queue(array|Throwable ...$responses): static {
        foreach ($responses as $response) {
            $this->responses[] = $response;
        }

        return $this;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function response(string $body = '', array $headers = [], int $status = 200): array {
        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    protected function send(
        string $method,
        string $path,
        array  $query,
        string $body,
        ?array $fileSlice,
        array  $headers,
        string $payloadHash,
    ): array {
        $this->requests[] = [
            'method'      => $method,
            'path'        => $path,
            'query'       => $query,
            'body'        => $body,
            'fileSlice'   => $fileSlice,
            'headers'     => $headers,
            'payloadHash' => $payloadHash,
        ];

        $response = array_shift($this->responses) ?? throw new LogicException("Unexpected request: $method $path");

        if ($response instanceof Throwable) {
            throw $response;
        }

        return $response;
    }

    protected function pause(int $seconds): void {
        $this->pauses[] = $seconds;
    }
}
