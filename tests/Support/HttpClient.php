<?php

declare(strict_types=1);

namespace BC\Tests\Support;

/**
 * HTTP requests with the streams of PHP (no extensions are needed): the redirects are not followed, the errors are
 * responses too.
 */
final readonly class HttpClient {
    public function __construct(
        private string $baseUrl
    ) {}

    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $path, string $body = '', array $headers = []): HttpResponse {
        if ($body !== '' && !array_any(array_keys($headers), static fn(string $name): bool => strcasecmp($name, 'Content-Type') === 0)) {
            $headers['Content-Type'] = 'application/json';
        }

        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = "$name: $value";
        }

        $context = stream_context_create([
            'http' => [
                'method'          => $method,
                'header'          => implode("\r\n", $headerLines),
                'content'         => $body,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ],
        ]);

        $responseBody = (string)@file_get_contents($this->baseUrl . $path, false, $context);

        return $this->parseResponse(http_get_last_response_headers() ?? [], $responseBody);
    }

    /**
     * @param array<mixed> $data
     * @param array<string, string> $headers
     */
    public function json(string $method, string $path, array $data, array $headers = []): HttpResponse {
        return $this->request($method, $path, (string)json_encode($data), $headers);
    }

    /**
     * @param string[] $headerLines
     */
    private function parseResponse(array $headerLines, string $body): HttpResponse {
        $code = 0;
        $headers = [];

        foreach ($headerLines as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $matches)) {
                $code = (int)$matches[1];
            } elseif (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))][] = trim($value);
            }
        }

        return new HttpResponse($code, $headers, $body);
    }
}
