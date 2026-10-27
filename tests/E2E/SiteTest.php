<?php

declare(strict_types=1);

namespace BC\Tests\E2E;

use BC\Tests\Support\ArrayPath;
use BC\Tests\Support\HttpClient;
use BC\Tests\Support\HttpResponse;
use BC\Tests\Support\HttpServer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The site behind the PHP built-in server: the admin API as a browser sees it (the session cookie, the hidden API, the
 * limit of the failed logins), a few public pages.
 */
#[CoversNothing]
final class SiteTest extends TestCase {
    use ArrayPath;

    private const string LOGIN = 'admin';

    private const string PASSWORD = 'correct horse battery';

    private static HttpServer $server;

    private static HttpClient $client;

    public static function setUpBeforeClass(): void {
        self::$server = new HttpServer();
        self::$server->start(self::LOGIN, self::PASSWORD);
        self::$client = new HttpClient(self::$server->getBaseUrl());
    }

    public static function tearDownAfterClass(): void {
        self::$server->stop();
    }

    protected function setUp(): void {
        // The limit of the failed logins is per address: every test starts without failed logins.
        self::$server->getDatabase()->exec('DELETE FROM `bc_login_attempts`');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideHiddenPaths(): iterable {
        yield 'API endpoint' => ['GET', '/admin/api/facts'];
        yield 'unknown API path' => ['GET', '/admin/api/no-such-endpoint'];
        yield 'wrong method (405)' => ['GET', '/admin/api/auth/login'];
        yield 'change' => ['DELETE', '/admin/api/facts'];
        yield 'admin area' => ['GET', '/admin'];
        yield 'admin page' => ['GET', '/admin/posts/1'];
    }

    /**
     * A guest sees no API and no admin area: the regular 404 page of the site.
     */
    #[DataProvider('provideHiddenPaths')]
    public function testGuestGetsTheNotFoundPage(string $method, string $path): void {
        $response = self::$client->request($method, $path);

        self::assertNotFoundPage($response);
    }

    public function testWrongCredentials(): void {
        $response = self::logIn(self::PASSWORD . '!');

        self::assertSame(401, $response->code, $response->body);
        self::assertStringStartsWith('application/json', (string)$response->getHeader('Content-Type'));
        self::assertIsArray(self::at($response->getJson(), 'errors'));
        self::assertNull($response->getHeader('Set-Cookie'));
    }

    public function testSessionOfTheAdmin(): void {
        $response = self::logIn(self::PASSWORD);

        self::assertSame(200, $response->code, $response->body . self::$server->getLog());
        $setCookie = (string)$response->getHeader('Set-Cookie');
        // HttpOnly: the scripts of the page cannot read it, SameSite=Strict: other sites cannot send it.
        self::assertMatchesRegularExpression('/^api_session=[0-9a-f]{64}; expires=[^;]+; Max-Age=\d+; path=\/; HttpOnly; SameSite=Strict$/', $setCookie);
        self::assertNull(self::arrayAt($response->getJson())['token'] ?? null, 'The token is in the cookie only.');
        $cookie = ['Cookie' => (string)strstr($setCookie, ';', true)];

        $response = self::$client->request('GET', '/admin/api/facts', headers: $cookie);
        self::assertSame(200, $response->code, $response->body);
        self::assertSame(['items' => [], 'total' => 0], $response->getJson());

        // The admin area itself.
        self::assertSame(200, self::$client->request('GET', '/admin', headers: $cookie)->code);

        // An unknown API path is a JSON error for the admin area.
        $response = self::$client->request('GET', '/admin/api/no-such-endpoint', headers: $cookie);
        self::assertSame(404, $response->code);
        self::assertStringStartsWith('application/json', (string)$response->getHeader('Content-Type'));
        self::assertIsArray(self::at($response->getJson(), 'errors'));

        $response = self::$client->request('POST', '/admin/api/auth/logout', headers: $cookie);
        self::assertSame(204, $response->code);
        self::assertStringStartsWith('api_session=deleted;', (string)$response->getHeader('Set-Cookie'));

        self::assertNotFoundPage(self::$client->request('GET', '/admin/api/facts', headers: $cookie));
    }

    public function testFactsOfTheAdmin(): void {
        $headers = [...self::logInWithCookie(), 'Origin' => self::$server->getBaseUrl()];

        $response = self::$client->json('POST', '/admin/api/fact', ['title' => ' Факт ', 'content' => 'Текст'], $headers);
        self::assertSame(200, $response->code, $response->body);
        $id = self::at($response->getJson(), 'id');
        self::assertIsInt($id);

        $response = self::$client->request('GET', "/admin/api/fact/$id", headers: $headers);
        self::assertSame(200, $response->code, $response->body);
        self::assertSame('Факт', self::at($response->getJson(), 'title'));
        self::assertSame('Текст', self::at($response->getJson(), 'content'));

        $response = self::$client->json('PUT', "/admin/api/fact/$id", ['title' => 'Новый', 'content' => 'Новый текст'], $headers);
        self::assertSame(200, $response->code, $response->body);
        self::assertSame('Новый', self::at(self::$client->request('GET', "/admin/api/fact/$id", headers: $headers)->getJson(), 'title'));

        $response = self::$client->json('POST', '/admin/api/fact', ['title' => '', 'content' => 'Текст'], $headers);
        self::assertSame(422, $response->code, $response->body);
        self::assertSame(['title' => 'Укажите название факта'], self::at($response->getJson(), 'errors'));

        $response = self::$client->request('GET', '/admin/api/facts', headers: $headers);
        self::assertSame(1, self::at($response->getJson(), 'total'));

        $response = self::$client->json('DELETE', '/admin/api/facts', ['rows' => [$id]], $headers);
        self::assertSame(200, $response->code, $response->body);
        self::assertSame(0, self::at(self::$client->request('GET', '/admin/api/facts', headers: $headers)->getJson(), 'total'));

        $response = self::$client->request('GET', "/admin/api/fact/$id", headers: $headers);
        self::assertSame(404, $response->code);
        self::assertSame("Факт #$id не найден.", self::at($response->getJson(), 'errors', 0, 'message'));
    }

    /**
     * A page of another site cannot make a change with the session cookie of the browser (CSRF).
     */
    public function testChangeOfAnotherSiteIsRefused(): void {
        $headers = [...self::logInWithCookie(), 'Origin' => 'https://evil.example'];

        $response = self::$client->json('POST', '/admin/api/fact', ['title' => 'Spam', 'content' => 'Spam'], $headers);

        self::assertSame(403, $response->code, $response->body);
        self::assertSame(0, self::at(self::$client->request('GET', '/admin/api/facts', headers: self::logInWithCookie())->getJson(), 'total'));
    }

    public function testTooManyFailedLogins(): void {
        for ($i = 0; $i < 5; $i++) {
            self::assertSame(401, self::logIn('wrong password')->code);
        }

        self::assertSame(429, self::logIn(self::PASSWORD)->code, 'Even the right password: the limit is reached.');
    }

    public function testStaticPage(): void {
        self::$server->getDatabase()->exec(
            "INSERT INTO `bc_static_pages` (title, short_title, content, slug, published, indexable, text_block,"
            . " publish_date, created_date, meta_description, back_link_text, back_link_url) VALUES ('Обо мне', '',"
            . " '{\"blocks\":[{\"type\":\"paragraph\",\"data\":{\"text\":\"Привет в доме\"}}]}', 'about', 1, 1, 1, NULL, "
            . time() . ", 'Описание', '', '')"
        );

        $response = self::$client->request('GET', '/about');

        self::assertSame(200, $response->code, $response->body . self::$server->getLog());
        self::assertStringStartsWith('text/html', (string)$response->getHeader('Content-Type'));
        self::assertStringContainsString('Обо мне', $response->body);
        self::assertSame('nosniff', $response->getHeader('X-Content-Type-Options'));
        self::assertNotFoundPage(self::$client->request('GET', '/no-such-page'));
    }

    public function testRedirects(): void {
        self::$server->getDatabase()->exec(
            "INSERT INTO `bc_redirects` (path, code, destination) VALUES ('/old-page', 301, '/about'), ('/gone', 410, NULL)"
        );

        $response = self::$client->request('GET', '/old-page');
        self::assertSame(301, $response->code);
        self::assertSame(self::$server->getBaseUrl() . '/about', $response->getHeader('Location'));

        $response = self::$client->request('GET', '/gone');
        self::assertSame(410, $response->code);
        self::assertStringContainsString('<!DOCTYPE html>', $response->body);
    }

    public function testFilesAreServedAsIs(): void {
        $response = self::$client->request('GET', '/robots.txt');

        self::assertSame(200, $response->code);
        self::assertSame((string)file_get_contents(PROJECT_ROOT . '/robots.txt'), $response->body);
    }

    private static function logIn(string $password): HttpResponse {
        return self::$client->json('POST', '/admin/api/auth/login', ['login' => self::LOGIN, 'password' => $password]);
    }

    /**
     * @return array{Cookie: string}
     */
    private static function logInWithCookie(): array {
        $response = self::logIn(self::PASSWORD);
        self::assertSame(200, $response->code, $response->body);

        return ['Cookie' => (string)strstr((string)$response->getHeader('Set-Cookie'), ';', true)];
    }

    private static function assertNotFoundPage(HttpResponse $response): void {
        self::assertSame(404, $response->code, $response->body);
        self::assertStringStartsWith('text/html', (string)$response->getHeader('Content-Type'));
        self::assertStringContainsString('<!DOCTYPE html>', $response->body);
    }
}
