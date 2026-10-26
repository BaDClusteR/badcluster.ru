<?php

declare(strict_types=1);

namespace BC\Core\Response;

use Runway\Request\Parameters\DTO\CookieDTO;

class SuccessfulJsonResponse extends JsonResponse {
    /**
     * @param array<mixed>          $data    The data to encode as JSON.
     * @param array<string, string> $headers
     * @param CookieDTO[]           $cookies
     */
    public function __construct(array $data = [], array $headers = [], array $cookies = []) {
        parent::__construct(200, $data, $headers, $cookies);
    }
}
