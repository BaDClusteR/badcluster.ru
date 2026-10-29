<?php

declare(strict_types=1);

namespace BC\Tests\Integration\Http;

use ApiPlatform\Core\Singleton\IAuth;
use BC\Model\Comment;
use BC\Model\Fact;
use BC\Model\StaticPage;
use BC\Tests\Fixture\Blog\BlogFixture;
use BC\Tests\Support\ArrayPath;
use BC\Tests\Support\InProcessRequests;
use BC\Tests\Support\SqliteSchema;
use DateTime;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

/**
 * The requests in the process of the test with the real container: the pages, the API hidden from the guests.
 */
#[CoversNothing]
final class PageRequestsTest extends TestCase {
    use ArrayPath;
    use InProcessRequests;

    private const string PASSWORD = 'correct horse battery';

    protected function setUp(): void {
        SqliteSchema::createSiteTables();
        Container::getInstance()->getService(IAuth::class)->setCredentials('admin', self::PASSWORD);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideHiddenPaths(): iterable {
        yield 'unknown page' => ['GET', '/no-such-page'];
        yield 'unknown nested page' => ['GET', '/no/such/page'];
        yield 'admin area' => ['GET', '/admin'];
        yield 'admin page' => ['GET', '/admin/posts'];
        yield 'API endpoint' => ['GET', '/admin/api/facts'];
        yield 'unknown API path' => ['GET', '/admin/api/no-such-endpoint'];
        yield 'wrong method of the login' => ['GET', '/admin/api/auth/login'];
        yield 'wrong method of an endpoint' => ['PATCH', '/admin/api/facts'];
        yield 'home slug' => ['GET', '/home'];
    }

    #[DataProvider('provideHiddenPaths')]
    public function testGuestGetsTheNotFoundPage(string $method, string $path): void {
        $response = $this->request($method, $path);

        self::assertSame(404, $response->getCode(), $response->getBody());
        self::assertStringContainsString('<!DOCTYPE html>', $response->getBody());
        self::assertStringNotContainsString('"errors"', $response->getBody());
    }

    public function testStaticPage(): void {
        self::createPage('about', 'Обо мне', true);
        self::createPage('draft', 'Черновик', false);

        $response = $this->request('GET', '/about');
        self::assertSame(200, $response->getCode());
        self::assertStringContainsString('Обо мне', $response->getBody());

        self::assertSame(404, $this->request('GET', '/draft')->getCode(), 'A draft is hidden from the guests.');
        self::assertSame(200, $this->request('GET', '/draft', headers: ['X-BC-Token' => $this->logIn()])->getCode(), 'The admin sees it.');
    }

    public function testLoggedInAdmin(): void {
        $token = $this->logIn();
        new Fact()->setTitle('Факт')->setContent('Текст')->persist();

        $response = $this->request('GET', '/admin/api/facts', headers: ['X-BC-Token' => $token]);
        self::assertSame(200, $response->getCode(), $response->getBody());
        self::assertSame(1, self::at(self::getJson($response), 'total'));
        self::assertSame('Факт', self::at(self::getJson($response), 'items', 0, 'title'));

        $response = $this->request('GET', '/admin/api/no-such-endpoint', headers: ['X-BC-Token' => $token]);
        self::assertSame(404, $response->getCode());
        self::assertIsArray(self::at(self::getJson($response), 'errors'));

        $response = $this->request('PATCH', '/admin/api/facts', headers: ['X-BC-Token' => $token]);
        self::assertSame(405, $response->getCode());

        $response = $this->request('GET', '/admin/posts', headers: ['X-BC-Token' => $token]);
        self::assertSame(200, $response->getCode());
    }

    public function testValidationErrorsOfTheApi(): void {
        $token = $this->logIn();

        $response = $this->request('POST', '/admin/api/fact', '{"title": " ", "content": ""}', ['X-BC-Token' => $token, 'Content-Type' => 'application/json']);

        self::assertSame(422, $response->getCode(), $response->getBody());
        self::assertSame('Ошибки при создании факта', self::at(self::getJson($response), 'message'));
        self::assertSame(['title' => 'Укажите название факта', 'content' => 'Укажите текст факта'], self::at(self::getJson($response), 'errors'));
    }

    public function testTagsInCommentsAreAllowedOnlyToTheAdmin(): void {
        $post = BlogFixture::createPost('commented');
        $text = "<b>Жирный</b> & \"кавычки\"\n<script>alert(1)</script>";

        foreach (['Гость' => [], 'Админ' => ['X-BC-Token' => $this->logIn()]] as $nickname => $headers) {
            $response = $this->request(
                'POST',
                '/comment',
                headers: $headers,
                form: ['type' => 'post', 'id' => (string)$post->getId(), 'nickname' => $nickname, 'comment' => $text, 'email' => '']
            );
            self::assertSame(200, $response->getCode(), $response->getBody());
        }

        $guest = Comment::findOne(['name' => 'Гость']);
        $admin = Comment::findOne(['name' => 'Админ']);
        self::assertNotNull($guest);
        self::assertNotNull($admin);

        self::assertSame("&lt;b&gt;Жирный&lt;/b&gt; &amp; \"кавычки\"\n&lt;script&gt;alert(1)&lt;/script&gt;", $guest->getComment());
        self::assertSame($text, $admin->getComment(), 'The text of the admin is saved as is.');
        self::assertTrue($admin->isApproved());

        $guest->approve();
        $page = $this->request('GET', '/blog/commented')->getBody();

        self::assertStringContainsString('<p>&lt;b&gt;Жирный&lt;/b&gt; &amp; "кавычки"</p><p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', $page);
        self::assertStringContainsString('<p><b>Жирный</b> & "кавычки"</p><p><script>alert(1)</script></p>', $page);
    }

    public function testWrongLogin(): void {
        $response = $this->request('POST', '/admin/api/auth/login', '{"login": "admin", "password": "wrong password"}', ['Content-Type' => 'application/json']);

        self::assertSame(401, $response->getCode());
    }

    private function logIn(): string {
        $response = $this->request(
            'POST',
            '/admin/api/auth/login',
            (string)json_encode(['login' => 'admin', 'password' => self::PASSWORD, 'useCookie' => false]),
            ['Content-Type' => 'application/json']
        );
        self::assertSame(200, $response->getCode(), $response->getBody());

        $token = self::at(self::getJson($response), 'token');
        self::assertIsString($token);

        return $token;
    }

    private static function createPage(string $slug, string $title, bool $isPublished): void {
        new StaticPage()
            ->setTitle($title)
            ->setSlug($slug)
            ->setContent(['blocks' => [['type' => 'paragraph', 'data' => ['text' => "Текст: $title"]]]])
            ->setPublished($isPublished)
            ->setCreatedDate(new DateTime())
            ->persist();
    }
}
