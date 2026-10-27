<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use PHPUnit\Framework\Assert;
use Runway\Request\IResponseRead;
use Runway\Request\Request;
use Runway\Router\IRouter;
use Runway\Singleton\Container;

/**
 * The requests handled in the process of the test: the globals of PHP, the Runway request with the real container of
 * the site.
 */
trait InProcessRequests {
    /**
     * @param array<string, string> $headers E.g. ["Origin" => "http://bc.test"].
     * @param array<string, string> $cookies
     * @param array<string, mixed>  $form    $_POST.
     * @param bool                  $catch   False: an exception is not turned into an error page, the test gets it
     *                                       (see IExceptionController).
     */
    protected function request(
        string $method,
        string $uri,
        string $body = '',
        array  $headers = [],
        array  $cookies = [],
        array  $form = [],
        bool   $catch = true
    ): IResponseRead {
        $server = $_SERVER;

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
        $_SERVER['HTTP_HOST'] = 'bc.test';

        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $_SERVER[in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $key : "HTTP_$key"] = $value;
        }

        $_GET = [];
        parse_str((string)parse_url($uri, PHP_URL_QUERY), $_GET);
        $_POST = $form;
        $_COOKIE = $cookies;

        try {
            $request = Request::resetInstance();
            $request->setBody($body);

            if (!$catch) {
                return Container::getInstance()->getService(IRouter::class)->process($request->getPath(), $request->getMethod());
            }

            try {
                return $request->process();
            } finally {
                // Request::process() sets the error handler of the request.
                restore_error_handler();
            }
        } finally {
            $_SERVER = $server;
            $_GET = $_POST = $_COOKIE = [];
            Request::resetInstance();
        }
    }

    /**
     * @return array<mixed>
     */
    protected static function getJson(IResponseRead $response): array {
        $data = json_decode($response->getBody(), true);
        Assert::assertIsArray($data, $response->getBody());

        return $data;
    }
}
