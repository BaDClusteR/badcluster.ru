<?php

namespace BC\Core\Response;

use Runway\Request\Response;

class HtmlResponse extends Response
{
    public function __construct(int $code = 0, string $body = '', array $headers = [], array $cookies = [])
    {
        if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        parent::__construct($code, $body, $headers, $cookies);
    }
}