<?php

declare(strict_types=1);

namespace BC\Core\Response;

class SuccessfulJsonResponse extends JsonResponse {
    public function __construct(array $data = [], array $headers = [], array $cookies = []) {
        parent::__construct(200, $data, $headers, $cookies);
    }
}
