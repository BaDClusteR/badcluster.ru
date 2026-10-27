<?php

declare(strict_types=1);

namespace BC\Tests\Integration;

use ApiPlatform\Controller\IApiController;
use ApiPlatform\Core\Provider\Endpoints\IEndpointsProvider;
use ApiPlatform\DTO\ApiEndpointDTO;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Console\Command\ICommand;
use Runway\Router\Matcher\IRouteMatcher;
use Runway\Singleton\Container;

/**
 * The routes of the site, of the modules and of the API endpoints compile without conflicts, and the key paths get
 * the expected controllers.
 */
#[CoversNothing]
final class RoutesTest extends TestCase {
    /**
     * @return iterable<string, array{string, string, string, array<string, string>}> Path => method, the route name
     *                                                                                 (or "api:" and the endpoint method),
     *                                                                                 the placeholder values.
     */
    public static function providePaths(): iterable {
        yield '/' => ['/', 'GET', 'index', []];
        yield '/comment' => ['/comment', 'POST', 'comment', []];
        yield '/admin' => ['/admin', 'GET', 'admin_index', []];
        yield '/admin/posts/1' => ['/admin/posts/1', 'GET', 'admin', ['path' => 'posts/1']];
        yield '/admin/apis' => ['/admin/apis', 'GET', 'admin', ['path' => 'apis']];
        yield '/admin/my/api' => ['/admin/my/api', 'GET', 'admin', ['path' => 'my/api']];
        yield '/login/notbot' => ['/login/notbot', 'GET', 'login', []];
        yield '/cringe' => ['/cringe', 'GET', 'cringe_museum', []];
        yield '/sitemap.xml' => ['/sitemap.xml', 'GET', 'sitemap', []];
        yield '/screenshots' => ['/screenshots', 'GET', 'screenshots', []];
        yield '/photo' => ['/photo', 'GET', 'photos', []];
        yield '/fact' => ['/fact', 'GET', 'random_fact', []];
        yield 'static page' => ['/about', 'GET', 'static_page', ['slug' => 'about']];
        yield '/blog beats /{slug}' => ['/blog', 'GET', 'posts_list', []];
        yield '/blog/page/2' => ['/blog/page/2', 'GET', 'posts_list_page', ['page' => '2']];
        yield '/blog/tag/php' => ['/blog/tag/php', 'GET', 'posts_list_tag', ['tag' => 'php']];
        yield '/blog/tag/php/page/3' => ['/blog/tag/php/page/3', 'GET', 'posts_list_tag_page', ['tag' => 'php', 'page' => '3']];
        yield '/blog/post' => ['/blog/my-post', 'GET', 'post', ['slug' => 'my-post']];
        yield '/notes' => ['/notes', 'GET', 'notes_list', []];
        yield '/notes/note' => ['/notes/a-note', 'GET', 'note', ['slug' => 'a-note']];
        yield '/books' => ['/books', 'GET', 'books', []];
        yield '/books/book' => ['/books/my-book', 'GET', 'books_book', ['book' => 'my-book']];
        yield '/books/book.fb2' => ['/books/my-book.fb2', 'GET', 'books_download', ['basename' => 'my-book', 'ext' => 'fb2']];
        yield '/books/book/chapter' => ['/books/my-book/chapter-1', 'GET', 'books_chapter_or_download', ['book' => 'my-book', 'slug' => 'chapter-1']];
        yield '/games' => ['/games', 'GET', 'games', []];
        yield '/games/game/material' => ['/games/doom/maps', 'GET', 'games_game', ['game' => 'doom', 'material' => 'maps']];
        yield '/music' => ['/music', 'GET', 'music', []];
        yield '/music/release' => ['/music/album', 'GET', 'release', ['releaseSlug' => 'album']];
        yield 'API list' => ['/admin/api/facts', 'GET', 'api:BC\Api\Endpoint\Facts::getList()', []];
        yield 'API item' => ['/admin/api/fact/42', 'GET', 'api:BC\Api\Endpoint\Facts::getOne()', ['identifier' => '42']];
        yield 'API create' => ['/admin/api/fact', 'POST', 'api:BC\Api\Endpoint\Facts::createFact()', []];
        yield 'API update' => ['/admin/api/fact/42', 'PUT', 'api:BC\Api\Endpoint\Facts::updateFact()', ['identifier' => '42']];
        yield 'API delete' => ['/admin/api/facts', 'DELETE', 'api:BC\Api\Endpoint\Facts::delete()', []];
        yield 'API login' => ['/admin/api/auth/login', 'POST', 'api:ApiPlatform\Endpoint\Auth::login()', []];
        yield 'API of a module' => ['/admin/api/books', 'GET', 'api:BC\Modules\Books\Api\Endpoint\Book::getList()', []];
    }

    /**
     * @param array<string, string> $parameters
     */
    #[DataProvider('providePaths')]
    public function testPath(string $path, string $method, string $expected, array $parameters): void {
        $match = self::getMatcher()->match($path, $method)->match;

        self::assertNotNull($match, "$method $path does not match.");

        if (str_starts_with($expected, 'api:')) {
            self::assertSame(IApiController::class, $match->route->controller);
            self::assertSame('handle', $match->route->method);
            self::assertSame(substr($expected, 4), $match->route->description);
        } else {
            self::assertSame($expected, $match->route->name);
        }

        self::assertSame($parameters, $match->parameters);
    }

    /**
     * The catch-all of the admin area excludes the API: an unknown API path is a 404 (JSON for the admin), not the page
     * of the admin area.
     */
    public function testApiPathsAreNotTheAdminArea(): void {
        $matcher = self::getMatcher();

        self::assertNull($matcher->match('/admin/api/no-such-endpoint', 'GET')->match);
        self::assertNull($matcher->match('/admin/api', 'GET')->match);
        self::assertNull($matcher->match('/admin/api/', 'GET')->match);
    }

    public function testWrongMethodOfAnEndpoint(): void {
        $result = self::getMatcher()->match('/admin/api/auth/login', 'GET');

        self::assertNull($result->match);
        self::assertSame(['POST'], $result->allowedMethods);
    }

    public function testNestedUnknownPathIsNotFound(): void {
        $result = self::getMatcher()->match('/no/such/page', 'GET');

        self::assertNull($result->match);
        self::assertSame([], $result->allowedMethods);
    }

    public function testEndpointsAreCollected(): void {
        $endpoints = Container::getInstance()->getService(IEndpointsProvider::class)->getApiEndpoints();
        $classes = array_unique(array_map(static fn(ApiEndpointDTO $endpoint): string => $endpoint->class, $endpoints));
        $keys = array_map(static fn(ApiEndpointDTO $endpoint): string => "$endpoint->requestMethod $endpoint->path", $endpoints);

        self::assertGreaterThan(80, count($endpoints));
        self::assertSame(array_unique($keys), $keys, 'Every endpoint has its own method and path.');

        foreach (Container::getInstance()->getServiceTagsByName('api_endpoint_collection') as $tag) {
            $class = get_class(Container::getInstance()->getService($tag['serviceName']));
            self::assertContains($class, $classes, "$class has no endpoints.");
        }

        foreach ($endpoints as $endpoint) {
            self::assertFalse(str_starts_with($endpoint->path, '/'), "$endpoint->path: the prefix is added by the router.");

            // Only the endpoints of API platform (the login and co.) can be public: the ones of the site need a session.
            if ($endpoint->isPublic) {
                self::assertStringStartsWith('ApiPlatform\\', $endpoint->class, "$endpoint->class::$endpoint->method() is public.");
            }
        }
    }

    public function testConsoleCommands(): void {
        $names = [];

        foreach (Container::getInstance()->getServicesByTag('console.command') as $command) {
            self::assertInstanceOf(ICommand::class, $command);
            $names[] = $command->getName();
        }

        self::assertSame(array_unique($names), $names);

        foreach (['auth:set-credentials', 'log:rotate', 'metrics:sample', 'assets:build', 'backup:run', 'backup:gdrive-auth'] as $name) {
            self::assertContains($name, $names);
        }
    }

    private static function getMatcher(): IRouteMatcher {
        return Container::getInstance()->getService(IRouteMatcher::class);
    }
}
