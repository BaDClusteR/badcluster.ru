<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Blog\Core\Action;

use BC\Modules\Blog\Core\Action\DTO\CreateNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\CreateTagRequest;
use BC\Modules\Blog\Core\Action\DTO\GetNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveTagRequest;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Core\Action\Note\ANoteAction;
use BC\Modules\Blog\Core\Action\Note\CreateNoteAction;
use BC\Modules\Blog\Core\Action\Note\GetNoteAction;
use BC\Modules\Blog\Core\Action\Note\SaveNoteAction;
use BC\Modules\Blog\Core\Action\Tag\CreateTagAction;
use BC\Modules\Blog\Core\Action\Tag\SaveTagAction;
use BC\Modules\Blog\Core\Action\Validator\NoteValidator;
use BC\Modules\Blog\Model\Note;
use BC\Modules\Blog\Model\Tag;
use BC\Tests\Fixture\Blog\BlogFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Exception\Exception;

#[CoversClass(ANoteAction::class)]
#[CoversClass(CreateNoteAction::class)]
#[CoversClass(SaveNoteAction::class)]
#[CoversClass(GetNoteAction::class)]
#[CoversClass(NoteValidator::class)]
#[CoversClass(CreateTagAction::class)]
#[CoversClass(SaveTagAction::class)]
#[CoversClass(Note::class)]
final class NoteAndTagActionsTest extends TestCase {
    protected function setUp(): void {
        BlogFixture::createTables();
    }

    public function testCreateNote(): void {
        $note = new CreateNoteAction()->run(new CreateNoteRequest(
            title: 'Title',
            content: ['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Text']]]],
            slug: 'note',
            metaDescription: 'Meta',
            published: true,
            publishDate: BlogFixture::date('2025-02-03 04:05:06')
        ))->note;

        $stored = Note::findByUniqueIdentifier($note->getId());
        self::assertNotNull($stored);
        self::assertSame('Title', $stored->getTitle());
        self::assertSame(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Text']]]], $stored->getContent());
        self::assertSame('note', $stored->getSlug());
        self::assertSame('Meta', $stored->getMetaDescription());
        self::assertTrue($stored->getPublished());
        self::assertSame(BlogFixture::date('2025-02-03 04:05:06')->getTimestamp(), $stored->getPublishDate()->getTimestamp());
        self::assertEqualsWithDelta(time(), $stored->getCreatedDate()->getTimestamp(), 60);
        self::assertSame('http://bc.test/notes/note', $stored->getUrl());
    }

    public function testCreateNoteWithTakenSlug(): void {
        $existing = BlogFixture::createNote('taken');

        try {
            new CreateNoteAction()->run(new CreateNoteRequest('T', [], 'Taken', '', false, BlogFixture::date('now')));
            self::fail('The slug is taken.');
        } catch (ActionValidationException $e) {
            self::assertSame(['slug' => sprintf('Такой слаг уже занят заметкой #%d "Note taken"', $existing->getId())], $e->getErrors());
        }

        self::assertCount(1, Note::find());
    }

    public function testSaveNote(): void {
        $note = BlogFixture::createNote('note');
        BlogFixture::createNote('other');

        new SaveNoteAction()->run(new SaveNoteRequest($note->getId(), 'New', ['blocks' => []], 'NOTE', 'Meta', false, BlogFixture::date('2025-05-05')));

        $stored = Note::findByUniqueIdentifier($note->getId());
        self::assertNotNull($stored);
        self::assertSame('New', $stored->getTitle());
        self::assertSame('NOTE', $stored->getSlug());
        self::assertFalse($stored->getPublished());

        try {
            new SaveNoteAction()->run(new SaveNoteRequest($note->getId(), 'New', [], 'Other', '', false, BlogFixture::date('now')));
            self::fail('The slug is taken.');
        } catch (ActionValidationException $e) {
            self::assertArrayHasKey('slug', $e->getErrors());
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Note #999 not found');
        new SaveNoteAction()->run(new SaveNoteRequest(999, 'New', [], 'free', '', false, BlogFixture::date('now')));
    }

    public function testGetNote(): void {
        $note = BlogFixture::createNote('note');

        self::assertSame($note->getId(), new GetNoteAction()->run(new GetNoteRequest($note->getId()))->note->getId());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Note #999 not found');
        new GetNoteAction()->run(new GetNoteRequest(999));
    }

    public function testCreateTag(): void {
        $tag = new CreateTagAction()->run(new CreateTagRequest('Doom', 'doom', 'About Doom'))->tag;

        $stored = Tag::findByUniqueIdentifier($tag->getId());
        self::assertNotNull($stored);
        self::assertSame('Doom', $stored->getTitle());
        self::assertSame('doom', $stored->getSlug());
        self::assertSame('About Doom', $stored->getDescription());
    }

    public function testCreateTagWithTakenSlugOrTitle(): void {
        BlogFixture::createTag('doom', 'Doom');

        $this->assertErrors(['slug' => "Этот слаг уже занят тэгом 'Doom'"], static fn() => new CreateTagAction()->run(new CreateTagRequest('Other', 'doom', '')));
        $this->assertErrors(['title' => 'Уже есть тэг с таким названием'], static fn() => new CreateTagAction()->run(new CreateTagRequest('Doom', 'other', '')));
        self::assertCount(1, Tag::find());

        new CreateTagAction()->run(new CreateTagRequest('Quake', 'quake', ''));
        self::assertCount(2, Tag::find());
    }

    public function testSaveTag(): void {
        $tag = BlogFixture::createTag('doom', 'Doom');
        BlogFixture::createTag('quake', 'Quake');

        // Its own slug and title are not taken.
        $saved = new SaveTagAction()->run(new SaveTagRequest($tag->getId(), 'Doom', 'doom', 'New description'))->tag;
        self::assertSame('New description', Tag::findByUniqueIdentifier($tag->getId())?->getDescription());
        self::assertSame($tag->getId(), $saved->getId());

        $this->assertErrors(['slug' => "Этот слаг уже занят тэгом 'Quake'"], static fn() => new SaveTagAction()->run(new SaveTagRequest($tag->getId(), 'Doom', 'quake', '')));
        $this->assertErrors(['title' => 'Уже есть тэг с таким названием'], static fn() => new SaveTagAction()->run(new SaveTagRequest($tag->getId(), 'Quake', 'doom', '')));
        $stored = Tag::findByUniqueIdentifier($tag->getId());
        self::assertSame('doom', $stored->getSlug());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tag #999 not found');
        new SaveTagAction()->run(new SaveTagRequest(999, 'Free', 'free', ''));
    }

    /**
     * @param array<string, string> $errors
     */
    private function assertErrors(array $errors, callable $action): void {
        try {
            $action();
            self::fail('The action must fail.');
        } catch (ActionValidationException $e) {
            self::assertSame($errors, $e->getErrors());
        }
    }
}
