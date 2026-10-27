<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Blog\Provider;

use BC\Modules\Blog\Model\Note;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Provider\NotesProvider;
use BC\Modules\Blog\Provider\PostsProvider;
use BC\Tests\Fixture\Blog\BlogFixture;
use BC\Tests\Fixture\Modules\InnerProviders;
use BC\Tests\Support\LoggerSpy;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostsProvider::class)]
#[CoversClass(NotesProvider::class)]
final class PostsAndNotesProviderTest extends TestCase {
    private LoggerSpy $logger;

    private PostsProvider $provider;

    protected function setUp(): void {
        BlogFixture::createTables();
        $this->logger = new LoggerSpy();
        $this->provider = new PostsProvider($this->logger);
    }

    public function testPages(): void {
        for ($i = 1; $i <= 21; $i++) {
            BlogFixture::createPost("post-$i", publishDate: sprintf('2025-01-%02d', $i));
        }

        self::assertSame(20, $this->provider->getShowBy());
        self::assertSame(21, $this->provider->getTotalPostsCount('', true));

        $first = $this->getSlugs($this->provider->getPosts('', 1, true));
        self::assertCount(20, $first);
        self::assertSame('post-21', $first[0]);
        self::assertSame('post-2', $first[19]);
        self::assertSame(['post-1'], $this->getSlugs($this->provider->getPosts('', 2, true)));
        self::assertNull($this->provider->getPosts('', 3, true));
        self::assertSame([], $this->logger->records);
    }

    public function testEmptyBlog(): void {
        self::assertNull($this->provider->getPosts('', 1, true));
        self::assertSame(0, $this->provider->getTotalPostsCount('', true));
    }

    public function testDrafts(): void {
        BlogFixture::createPost('published', publishDate: '2025-01-01');
        BlogFixture::createPost('draft', published: false, publishDate: '2025-01-02');

        self::assertSame(['published'], $this->getSlugs($this->provider->getPosts('', 1, true)));
        self::assertSame(['draft', 'published'], $this->getSlugs($this->provider->getPosts('', 1, false)));
        self::assertSame(1, $this->provider->getTotalPostsCount('', true));
        self::assertSame(2, $this->provider->getTotalPostsCount('', false));
    }

    public function testTag(): void {
        $doom = BlogFixture::createTag('doom');
        $quake = BlogFixture::createTag('quake');
        BlogFixture::createPost('doom-1', publishDate: '2025-01-01')->syncTags([$doom]);
        BlogFixture::createPost('both', publishDate: '2025-01-03')->syncTags([$doom, $quake]);
        BlogFixture::createPost('quake-1', publishDate: '2025-01-02')->syncTags([$quake]);
        BlogFixture::createPost('doom-draft', published: false, publishDate: '2025-01-04')->syncTags([$doom]);
        BlogFixture::createPost('untagged', publishDate: '2025-01-05');

        self::assertSame(['both', 'doom-1'], $this->getSlugs($this->provider->getPosts('doom', 1, true)));
        self::assertSame(['doom-draft', 'both', 'doom-1'], $this->getSlugs($this->provider->getPosts('doom', 1, false)));
        self::assertSame(['both', 'quake-1'], $this->getSlugs($this->provider->getPosts('quake', 1, true)));
        self::assertSame(2, $this->provider->getTotalPostsCount('doom', true));
        self::assertSame(3, $this->provider->getTotalPostsCount('doom', false));
    }

    public function testUnknownTagHasNoPosts(): void {
        // A tag with the smallest id: the unknown tag is not taken for it.
        $tag = BlogFixture::createTag('first');
        self::assertSame(1, $tag->getId());
        BlogFixture::createPost('post')->syncTags([$tag]);

        self::assertNull($this->provider->getPosts('unknown', 1, true));
        self::assertSame(0, $this->provider->getTotalPostsCount('unknown', true));
    }

    public function testDatabaseErrors(): void {
        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{posts}`');

        self::assertNull($this->provider->getPosts('', 2, true));
        self::assertSame(0, $this->provider->getTotalPostsCount('', false));

        $errors = $this->logger->getRecords('error');
        self::assertCount(2, $errors);
        self::assertStringStartsWith('[' . PostsProvider::class . '::getPosts] Cannot count posts: ', $errors[0]['message']);
        self::assertSame(['tag' => '', 'page' => 2, 'onlyPublished' => true], array_slice($errors[0]['context'], 0, 3));
        self::assertArrayHasKey('errCode', $errors[0]['context']);
        $errorMessage = $errors[0]['context']['errMessage'] ?? null;
        self::assertIsString($errorMessage);
        self::assertStringContainsString('posts', $errorMessage);
        self::assertStringStartsWith('[' . PostsProvider::class . '::getTotalPostsCount] Cannot get posts count: ', $errors[1]['message']);
        self::assertSame(['tag' => '', 'onlyPublished' => false], array_slice($errors[1]['context'], 0, 2));
        self::assertSame(['tag', 'onlyPublished', 'errCode', 'errMessage'], array_keys($errors[1]['context']));
        self::assertSame(['tag', 'page', 'onlyPublished', 'errCode', 'errMessage'], array_keys($errors[0]['context']));

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{tags}`');
        self::assertNull($this->provider->getPosts('doom', 1, false));
        self::assertStringStartsWith('[' . PostsProvider::class . '::getPosts] Cannot get posts: ', $this->logger->getMessages('error')[2]);
    }

    public function testNotes(): void {
        BlogFixture::createNote('old', publishDate: '2025-01-01');
        BlogFixture::createNote('draft', published: false, publishDate: '2025-01-03');
        BlogFixture::createNote('new', publishDate: '2025-01-02');
        $guest = new NotesProvider($this->logger, new InnerProviders(isAuthenticated: false));

        self::assertSame(['new', 'old'], $this->getSlugs($guest->getNotes(true)));
        self::assertSame(['draft', 'new', 'old'], $this->getSlugs($guest->getNotes(false)));
        self::assertTrue($guest->hasNotes());
    }

    public function testHasNotesDependsOnAuthentication(): void {
        BlogFixture::createNote('draft', published: false);

        self::assertFalse(new NotesProvider($this->logger, new InnerProviders(isAuthenticated: false))->hasNotes());
        self::assertTrue(new NotesProvider($this->logger, new InnerProviders(isAuthenticated: true))->hasNotes());
    }

    public function testNotesError(): void {
        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{notes}`');
        $provider = new NotesProvider($this->logger, new InnerProviders());

        self::assertFalse($provider->hasNotes());

        $errors = $this->logger->getRecords('error');
        self::assertCount(1, $errors);
        self::assertStringStartsWith('[' . NotesProvider::class . '::getNotes] Cannot get notes: ', $errors[0]['message']);
        self::assertSame(['onlyPublished', 'errCode', 'errMessage'], array_keys($errors[0]['context']));
        self::assertTrue($errors[0]['context']['onlyPublished']);
    }

    /**
     * @param iterable<mixed>|null $entities Posts or notes.
     *
     * @return list<string>
     */
    private function getSlugs(?iterable $entities): array {
        self::assertNotNull($entities);
        $slugs = [];

        foreach ($entities as $entity) {
            self::assertTrue($entity instanceof Post || $entity instanceof Note);
            $slugs[] = $entity->getSlug();
        }

        return $slugs;
    }
}
