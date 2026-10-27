<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Blog\Core\Action;

use BC\Model\Media;
use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\Event\CreatedPostEnvelope;
use BC\Modules\Blog\Core\Action\DTO\Event\CreatePostEnvelope;
use BC\Modules\Blog\Core\Action\DTO\Event\SavedPostEnvelope;
use BC\Modules\Blog\Core\Action\DTO\Event\SavePostEnvelope;
use BC\Modules\Blog\Core\Action\DTO\GetPostRequest;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Core\Action\Post\APostAction;
use BC\Modules\Blog\Core\Action\Post\CreatePostAction;
use BC\Modules\Blog\Core\Action\Post\GetPostAction;
use BC\Modules\Blog\Core\Action\Post\SavePostAction;
use BC\Modules\Blog\Core\Action\Validator\PostValidator;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\PostTag;
use BC\Modules\Blog\Model\Tag;
use BC\Tests\Fixture\Blog\BlogFixture;
use BC\Tests\Support\EventDispatcherSpy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Exception\Exception;

#[CoversClass(APostAction::class)]
#[CoversClass(CreatePostAction::class)]
#[CoversClass(SavePostAction::class)]
#[CoversClass(GetPostAction::class)]
#[CoversClass(PostValidator::class)]
#[CoversClass(ActionValidationException::class)]
#[CoversClass(Post::class)]
final class PostActionsTest extends TestCase {
    private EventDispatcherSpy $events;

    protected function setUp(): void {
        BlogFixture::createTables();
        $this->events = new EventDispatcherSpy();
    }

    public function testCreate(): void {
        $tag = BlogFixture::createTag('doom');
        $cover = BlogFixture::createMedia('cover.jpg');
        $request = $this->createRequest('new-post', [$tag], $cover);

        $response = new CreatePostAction($this->events)->run($request);

        $post = Post::findByUniqueIdentifier($response->post->getId());
        self::assertNotNull($post);
        self::assertSame('Title', $post->getTitle());
        self::assertSame('Short', $post->getShortTitle());
        self::assertSame('Subtitle', $post->getSubtitle());
        self::assertSame('Annotation', $post->getAnnotation());
        self::assertSame(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Text']]]], $post->getContent());
        self::assertSame('new-post', $post->getSlug());
        self::assertSame('Meta', $post->getMetaDescription());
        self::assertTrue($post->getPublished());
        self::assertSame(BlogFixture::date('2025-02-03 04:05:06')->getTimestamp(), $post->getPublishDate()->getTimestamp());
        self::assertSame(BlogFixture::date('2025-03-03 00:00:00')->getTimestamp(), $post->getUpdateDate()?->getTimestamp());
        self::assertEqualsWithDelta(time(), $post->getCreatedDate()->getTimestamp(), 60);
        self::assertSame($cover->getId(), $post->getCover()?->getId());
        self::assertSame('Alt text', Media::findByUniqueIdentifier($cover->getId())?->getAlt());
        self::assertSame(['doom'], array_map(static fn(Tag $tag): string => $tag->getSlug(), $post->getTags()));

        self::assertSame(['post.create.before', 'post.create.after'], array_column($this->events->events, 'name'));
        self::assertEquals(new CreatePostEnvelope($request), $this->events->events[0]['envelope']);
        $after = $this->events->events[1]['envelope'];
        self::assertInstanceOf(CreatedPostEnvelope::class, $after);
        self::assertSame($request, $after->request);
        self::assertSame($response, $after->response);
    }

    public function testCreateWithTakenSlug(): void {
        $existing = BlogFixture::createPost('taken', title: 'Old');

        try {
            new CreatePostAction($this->events)->run($this->createRequest('TAKEN'));
            self::fail('The slug is taken.');
        } catch (ActionValidationException $e) {
            self::assertSame(['slug' => sprintf('Такой слаг уже занят постом #%d "Old"', $existing->getId())], $e->getErrors());
            self::assertSame('Validation failed', $e->getMessage());
            self::assertSame(0, $e->getCode());
        }

        self::assertCount(1, Post::find());
        // The "before" event goes first: a listener may change the request.
        self::assertSame(['post.create.before'], array_column($this->events->events, 'name'));
    }

    public function testSave(): void {
        $post = BlogFixture::createPost('old-slug');
        $kept = BlogFixture::createTag('kept');
        $removed = BlogFixture::createTag('removed');
        $added = BlogFixture::createTag('added');
        $post->syncTags([$kept, $removed]);
        $keptLink = PostTag::findOne(['post' => $post, 'tag' => $kept]);
        self::assertNotNull($keptLink);

        $request = $this->createSaveRequest($post->getId(), 'OLD-SLUG', [$kept, $added]);
        new SavePostAction($this->events)->run($request);

        $saved = Post::findByUniqueIdentifier($post->getId());
        self::assertNotNull($saved);
        self::assertSame('OLD-SLUG', $saved->getSlug());
        self::assertSame('Title', $saved->getTitle());
        self::assertNull($saved->getUpdateDate());
        self::assertSame(['added', 'kept'], $this->getTagSlugs($saved));
        // The link of the kept tag stays as is: only the others are deleted and created.
        self::assertSame($keptLink->getId(), PostTag::findOne(['post' => $post, 'tag' => $kept])?->getId());
        self::assertCount(2, PostTag::find(['post' => $post]));

        self::assertSame(['post.save.before', 'post.save.after'], array_column($this->events->events, 'name'));
        self::assertEquals(new SavePostEnvelope($request), $this->events->events[0]['envelope']);
        $after = $this->events->events[1]['envelope'];
        self::assertInstanceOf(SavedPostEnvelope::class, $after);
        self::assertSame($request, $after->request);
        self::assertSame($post->getId(), $after->post->getId());
    }

    public function testSaveKeepsSameCover(): void {
        $cover = BlogFixture::createMedia('cover.jpg');
        $post = BlogFixture::createPost('post', cover: $cover);

        new SavePostAction($this->events)->run($this->createSaveRequest($post->getId(), 'post', [], $cover));

        self::assertSame($cover->getId(), Post::findByUniqueIdentifier($post->getId())?->getCover()?->getId());
        self::assertNotNull(Media::findByUniqueIdentifier($cover->getId()));
    }

    public function testSaveWithSlugOfOtherPost(): void {
        $post = BlogFixture::createPost('mine');
        $other = BlogFixture::createPost('other', title: 'Other');

        $this->expectExceptionObject(new ActionValidationException([]));

        try {
            new SavePostAction($this->events)->run($this->createSaveRequest($post->getId(), 'Other'));
        } catch (ActionValidationException $e) {
            self::assertSame(['slug' => sprintf('Такой слаг уже занят постом #%d "Other"', $other->getId())], $e->getErrors());
            self::assertSame('mine', Post::findByUniqueIdentifier($post->getId())?->getSlug());

            throw $e;
        }
    }

    public function testSaveOfUnknownPost(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Post #42 not found');

        new SavePostAction($this->events)->run($this->createSaveRequest(42, 'slug'));
    }

    public function testGet(): void {
        $post = BlogFixture::createPost('post');

        self::assertSame($post->getId(), new GetPostAction()->run(new GetPostRequest($post->getId()))->post->getId());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Post #999 not found');
        new GetPostAction()->run(new GetPostRequest(999));
    }

    public function testSyncTagsWithoutTags(): void {
        $post = BlogFixture::createPost('post');
        $other = BlogFixture::createPost('other');
        $tag = BlogFixture::createTag('tag');
        $post->syncTags([$tag]);
        $other->syncTags([$tag]);

        $post->syncTags([]);

        self::assertSame([], $this->getTagSlugs(Post::findByUniqueIdentifier($post->getId())));
        self::assertSame(['tag'], $this->getTagSlugs(Post::findByUniqueIdentifier($other->getId())));
    }

    public function testSyncTagsSkipsUnknownTag(): void {
        $post = BlogFixture::createPost('post');
        $tag = BlogFixture::createTag('tag');
        $ghost = new Tag()->setId(999);

        $post->syncTags([$tag, $ghost]);

        self::assertSame(['tag'], $this->getTagSlugs($post));
    }

    public function testRemoveDeletesTagLinks(): void {
        $post = BlogFixture::createPost('post');
        $other = BlogFixture::createPost('other');
        $tag = BlogFixture::createTag('tag');
        $post->syncTags([$tag]);
        $other->syncTags([$tag]);

        $post->remove();

        self::assertNull(Post::findByUniqueIdentifier($post->getId()));
        self::assertSame([], PostTag::find(['post' => $post->getId()]));
        self::assertSame(['tag'], $this->getTagSlugs(Post::findByUniqueIdentifier($other->getId())));
        self::assertNotNull(Tag::findByUniqueIdentifier($tag->getId()));
    }

    public function testUrls(): void {
        self::assertSame('http://bc.test/blog/my-post', new Post()->setSlug('my-post')->getUrl());
        self::assertSame('http://bc.test/blog/tag/doom', new Tag()->setSlug('doom')->getUrl());
    }

    /**
     * @return list<string>
     */
    private function getTagSlugs(?Post $post): array {
        self::assertNotNull($post);
        $slugs = array_map(static fn(Tag $tag): string => $tag->getSlug(), $post->getTags());
        sort($slugs);

        return $slugs;
    }

    /**
     * @param Tag[] $tags
     */
    private function createRequest(string $slug, array $tags = [], ?Media $cover = null): CreatePostRequest {
        return new CreatePostRequest(
            title: 'Title',
            shortTitle: 'Short',
            subtitle: 'Subtitle',
            annotation: 'Annotation',
            content: ['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Text']]]],
            slug: $slug,
            metaDescription: 'Meta',
            published: true,
            publishDate: BlogFixture::date('2025-02-03 04:05:06'),
            updateDate: BlogFixture::date('2025-03-03 00:00:00'),
            coverImage: $cover,
            coverImageAltText: 'Alt text',
            tags: $tags
        );
    }

    /**
     * @param Tag[] $tags
     */
    private function createSaveRequest(int $id, string $slug, array $tags = [], ?Media $cover = null): SavePostRequest {
        return new SavePostRequest(
            id: $id,
            title: 'Title',
            shortTitle: 'Short',
            subtitle: 'Subtitle',
            annotation: 'Annotation',
            content: ['blocks' => []],
            slug: $slug,
            metaDescription: 'Meta',
            published: false,
            publishDate: BlogFixture::date('2025-02-03 04:05:06'),
            updateDate: null,
            coverImage: $cover,
            coverImageAltText: '',
            tags: $tags
        );
    }
}
