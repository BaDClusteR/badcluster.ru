<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use Runway\Request\Request;

/**
 * A Runway request of the unit tests: "GET https://bc.test/" from 203.0.113.5, a web request unless $isCli is set; the
 * rest is set by the setters.
 */
final class TestRequest extends Request {
    public bool $isCli = false;

    /**
     * @param array<string, string> $headers
     */
    public static function create(string $method = 'GET', string $path = '/', array $headers = []): self {
        return new self(new EmptyRequestParameters(), new LoggerSpy())
            ->setMethod($method)
            ->setPath($path)
            ->setRequestUri($path)
            ->setHeaders($headers)
            ->setRequestId('request-id');
    }

    public function isCLI(): bool {
        return $this->isCli;
    }
}
