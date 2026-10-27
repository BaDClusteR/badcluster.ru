<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Uploader\GoogleDrive\GoogleDriveClient;
use LogicException;
use Throwable;

/**
 * Records the requests instead of sending them: the responses (or the exceptions) are given by the test, in order.
 * A response can have headers: they are passed to the header callback of the request (CURLOPT_HEADERFUNCTION).
 */
final class GoogleDriveClientSpy extends GoogleDriveClient {
    /** @var list<array{url: string, options: array<int, mixed>, skipAuthHeader: bool}> */
    public array $requests = [];

    /** @var list<array{body: string, headers: list<string>}|Throwable> */
    private array $responses = [];

    /**
     * @param list<string> $headers E.g. "Location: https://upload.example/session".
     */
    public function queue(string $body, array $headers = []): static {
        $this->responses[] = ['body' => $body, 'headers' => $headers];

        return $this;
    }

    public function queueException(Throwable $exception): static {
        $this->responses[] = $exception;

        return $this;
    }

    /**
     * @param array<int, mixed> $options
     */
    protected function rawRequest(string $url, array $options, bool $skipAuthHeader = false): string {
        $this->requests[] = ['url' => $url, 'options' => $options, 'skipAuthHeader' => $skipAuthHeader];

        $response = array_shift($this->responses) ?? throw new LogicException("Unexpected request: $url");

        if ($response instanceof Throwable) {
            throw $response;
        }

        $callback = $options[CURLOPT_HEADERFUNCTION] ?? null;

        foreach ($response['headers'] as $header) {
            if (is_callable($callback)) {
                $callback(null, "$header\r\n");
            }
        }

        return $response['body'];
    }
}
