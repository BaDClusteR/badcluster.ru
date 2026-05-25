<?php

declare(strict_types=1);

namespace BC\Core\Response;

use Runway\Request\Response;

class RedirectResponse extends Response {
    public function __construct(string $url, array $headers = [], array $cookies = []) {
        $headers['Location'] = $url;

        parent::__construct(301, '', $headers, $cookies);
    }
}
