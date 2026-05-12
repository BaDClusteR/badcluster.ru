<?php

namespace BC\Core\Response;

class SuccessfulHtmlResponse extends HtmlResponse
{
    public function __construct(string $body = '', array $headers = [], array $cookies = [])
    {
        parent::__construct(200, $body, $headers, $cookies);
    }
}
