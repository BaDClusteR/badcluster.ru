<?php

declare(strict_types=1);

namespace BC\Core\Response;

use JsonException;
use Runway\Request\Parameters\DTO\CookieDTO;
use Runway\Request\Response;

class JsonResponse extends Response {
    /**
     * @param array<mixed>          $data    The data to encode as JSON.
     * @param array<string, string> $headers
     * @param CookieDTO[]           $cookies
     */
    public function __construct(int $code = 0, array $data = [], array $headers = [], array $cookies = []) {
        if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (JsonException) {
            $body = '{}';
        }

        parent::__construct($code, $body, $headers, $cookies);
    }
}
