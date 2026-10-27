<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Blog\Api;

use BC\Core\Converter\DateConverter;
use BC\Core\Converter\Media\MediaConverter;
use BC\Core\Helper\IBlockHelper;
use BC\Modules\Blog\Api\DataBuilder\Note\NoteDataBuilder;
use BC\Modules\Blog\Api\DataBuilder\Post\BlogPostDataBuilder;
use BC\Modules\Blog\Api\DataBuilder\Tag\TagDataBuilder;
use BC\Modules\Blog\Model\Tag;
use BC\Tests\Fixture\Blog\BlogFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

#[CoversClass(NoteDataBuilder::class)]
#[CoversClass(BlogPostDataBuilder::class)]
#[CoversClass(TagDataBuilder::class)]
final class DataBuilderTest extends TestCase {
    private DateConverter $dateConverter;

    private IBlockHelper $blockHelper;

    protected function setUp(): void {
        BlogFixture::createTables();
        $this->dateConverter = new DateConverter('Europe/Samara');
        $this->blockHelper = Container::getInstance()->getService(IBlockHelper::class);
    }

    public function testPostRow(): void {
        $builder = $this->createPostBuilder();
        $post = BlogFixture::createPost('post', publishDate: '2025-02-03 21:00:00', title: 'Long title');

        $row = $builder->buildRow($post);
        self::assertSame($post->getId(), $row->id);
        self::assertSame('Long title', $row->title);
        self::assertSame('post', $row->slug);
        self::assertTrue($row->published);
        self::assertSame('4 фев 2025', $row->publish_date);
        self::assertSame('', $row->updateDate);

        $post->setShortTitle('Short')->setUpdateDate(BlogFixture::date('2025-05-06 00:00:00'));
        $row = $builder->buildRow($post);
        self::assertSame('Short', $row->title);
        self::assertSame('6 мая 2025', $row->updateDate);

        $post->setPublished(false);
        $row = $builder->buildRow($post);
        self::assertFalse($row->published);
        self::assertSame('—', $row->publish_date);
        self::assertSame('', $row->updateDate);
    }

    public function testPostEntity(): void {
        $cover = BlogFixture::createMedia('cover.jpg', 'Alt');
        $image = BlogFixture::createMedia('image.jpg', 'Image');
        $post = BlogFixture::createPost('post', publishDate: '2025-02-03 21:00:00', title: 'Title', cover: $cover, annotation: 'Annotation');
        $post->setShortTitle('Short')->setSubtitle('Sub')->setMetaDescription('Meta')
            ->setContent(['blocks' => [['type' => 'media', 'data' => ['media' => ['id' => $image->getId()]]]]]);
        $post->persist();
        $tags = [BlogFixture::createTag('b'), BlogFixture::createTag('a')];
        $post->syncTags($tags);

        $dto = $this->createPostBuilder()->buildEntity($post);

        self::assertSame($post->getId(), $dto->id);
        self::assertSame('Title', $dto->title);
        self::assertSame('Short', $dto->shortTitle);
        self::assertSame('Sub', $dto->subtitle);
        self::assertSame('Meta', $dto->metaDescription);
        self::assertSame('Annotation', $dto->annotation);
        self::assertSame($cover->getId(), $dto->coverImage['id'] ?? null);
        self::assertSame('Alt', $dto->coverImage['alt'] ?? null);
        self::assertSame('2025-02-04T01:00:00+04:00', $dto->publishDate);
        self::assertNull($dto->updateDate);
        $media = self::at($dto->content, 'blocks', 0, 'data', 'media');
        self::assertSame($image->getId(), self::at($media, 'id'));
        self::assertSame('http://bc.test/static/media/image.jpg', self::at($media, 'url'));
        self::assertSame('Image', self::at($media, 'alt'));
        self::assertTrue($dto->published);
        self::assertSame('post', $dto->slug);
        $ids = $dto->tags;
        sort($ids);
        self::assertSame(array_map(static fn(Tag $tag): string => (string)$tag->getId(), [$tags[0], $tags[1]]), $ids);

        $post->setUpdateDate(BlogFixture::date('2025-05-06 00:00:00'))->setCover(null);
        $dto = $this->createPostBuilder()->buildEntity($post);
        self::assertSame('2025-05-06T04:00:00+04:00', $dto->updateDate);
        self::assertNull($dto->coverImage);
    }

    public function testNote(): void {
        $builder = new NoteDataBuilder($this->dateConverter, $this->blockHelper);
        $note = BlogFixture::createNote('note', publishDate: '2025-02-03 21:00:00');
        $note->setMetaDescription('Meta')->setContent(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'T']]]]);

        $row = $builder->buildRow($note);
        self::assertSame([$note->getId(), 'Note note', 'note', true, '4 фев 2025'], [$row->id, $row->title, $row->slug, $row->published, $row->publish_date]);
        self::assertSame('—', $builder->buildRow($note->setPublished(false))->publish_date);

        $dto = $builder->buildEntity($note);
        self::assertSame($note->getId(), $dto->id);
        self::assertSame('Note note', $dto->title);
        self::assertSame('Meta', $dto->metaDescription);
        self::assertSame('2025-02-04T01:00:00+04:00', $dto->publishDate);
        self::assertSame(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'T']]]], $dto->content);
        self::assertFalse($dto->published);
        self::assertSame('note', $dto->slug);
    }

    public function testTag(): void {
        $builder = new TagDataBuilder();

        $row = $builder->buildRow(['id' => '5', 'title' => 'Doom', 'slug' => 'doom', 'count' => '3']);
        self::assertSame([5, 'Doom', 'doom', 3], [$row->id, $row->title, $row->slug, $row->count]);

        $row = $builder->buildRow([]);
        self::assertSame([0, '', '', 0], [$row->id, $row->title, $row->slug, $row->count]);

        $dto = $builder->buildEntity(new Tag()->setTitle('Doom')->setSlug('doom')->setDescription('About'));
        self::assertSame(['Doom', 'doom', 'About'], [$dto->title, $dto->slug, $dto->description]);
    }

    /**
     * The value by its path: a missing key fails the test.
     */
    private static function at(mixed $data, int|string ...$keys): mixed {
        foreach ($keys as $key) {
            self::assertIsArray($data);
            self::assertArrayHasKey($key, $data);
            $data = $data[$key];
        }

        return $data;
    }

    private function createPostBuilder(): BlogPostDataBuilder {
        return new BlogPostDataBuilder($this->dateConverter, new MediaConverter(), $this->blockHelper);
    }
}
