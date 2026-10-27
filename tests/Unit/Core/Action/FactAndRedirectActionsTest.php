<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Action;

use BC\Core\Action\DTO\CreateFactRequest;
use BC\Core\Action\DTO\CreateRedirectRequest;
use BC\Core\Action\DTO\SaveFactRequest;
use BC\Core\Action\DTO\SaveRedirectRequest;
use BC\Core\Action\Fact\AFactAction;
use BC\Core\Action\Fact\CreateFactAction;
use BC\Core\Action\Fact\SaveFactAction;
use BC\Core\Action\Redirect\ARedirectAction;
use BC\Core\Action\Redirect\CreateRedirectAction;
use BC\Core\Action\Redirect\SaveRedirectAction;
use BC\Model\Fact;
use BC\Model\Redirect;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Exception\Exception;

#[CoversClass(AFactAction::class)]
#[CoversClass(CreateFactAction::class)]
#[CoversClass(SaveFactAction::class)]
#[CoversClass(ARedirectAction::class)]
#[CoversClass(CreateRedirectAction::class)]
#[CoversClass(SaveRedirectAction::class)]
#[CoversClass(ActionValidationException::class)]
final class FactAndRedirectActionsTest extends TestCase {
    protected function setUp(): void {
        SqliteSchema::createModelTables(Fact::class, Redirect::class);
    }

    public function testCreateFact(): void {
        $fact = new CreateFactAction()->run(new CreateFactRequest('  Заголовок ', "\n Текст \n"))->fact;

        $stored = Fact::findByUniqueIdentifier($fact->getId());
        self::assertNotNull($stored);
        self::assertSame(['Заголовок', 'Текст'], [$stored->getTitle(), $stored->getContent()]);
    }

    public function testInvalidFact(): void {
        $errors = self::getErrors(static fn() => new CreateFactAction()->run(new CreateFactRequest(' ', "\n")));

        self::assertSame(['title' => 'Укажите название факта', 'content' => 'Укажите текст факта'], $errors);
        self::assertSame([], Fact::find());
    }

    public function testFactWithoutContent(): void {
        self::assertSame(
            ['content' => 'Укажите текст факта'],
            self::getErrors(static fn() => new CreateFactAction()->run(new CreateFactRequest('Title', '')))
        );
        self::assertSame(
            ['title' => 'Укажите название факта'],
            self::getErrors(static fn() => new CreateFactAction()->run(new CreateFactRequest('', 'Content')))
        );
    }

    public function testSaveFact(): void {
        $fact = new CreateFactAction()->run(new CreateFactRequest('Old', 'Old text'))->fact;
        $other = new CreateFactAction()->run(new CreateFactRequest('Other', 'Other text'))->fact;

        new SaveFactAction()->run(new SaveFactRequest($fact->getId(), ' New ', ' New text '));

        $stored = Fact::findByUniqueIdentifier($fact->getId());
        self::assertNotNull($stored);
        self::assertSame(['New', 'New text'], [$stored->getTitle(), $stored->getContent()]);
        self::assertSame('Other', Fact::findByUniqueIdentifier($other->getId())?->getTitle());
    }

    public function testSaveInvalidFact(): void {
        $fact = new CreateFactAction()->run(new CreateFactRequest('Old', 'Old text'))->fact;

        self::assertSame(
            ['title' => 'Укажите название факта'],
            self::getErrors(static fn() => new SaveFactAction()->run(new SaveFactRequest($fact->getId(), '', 'x')))
        );
        self::assertSame('Old', Fact::findByUniqueIdentifier($fact->getId())?->getTitle());
    }

    public function testSaveUnknownFact(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Fact #404 not found');

        new SaveFactAction()->run(new SaveFactRequest(404, 'Title', 'Text'));
    }

    public function testCreatePermanentRedirect(): void {
        $redirect = new CreateRedirectAction()->run(new CreateRedirectRequest(' /old ', 301, ' /new '))->redirect;

        $stored = Redirect::findByUniqueIdentifier($redirect->getId());
        self::assertNotNull($stored);
        self::assertSame(['/old', 301, '/new'], [$stored->getPath(), $stored->getCode(), $stored->getDestination()]);
    }

    public function testGoneRedirectHasNoDestination(): void {
        $redirect = new CreateRedirectAction()->run(new CreateRedirectRequest('/gone', 410, '/ignored'))->redirect;

        $stored = Redirect::findByUniqueIdentifier($redirect->getId());
        self::assertNotNull($stored);
        self::assertSame(410, $stored->getCode());
        self::assertNull($stored->getDestination());
    }

    public function testInvalidRedirect(): void {
        self::assertSame(
            ['path' => 'Укажите путь', 'code' => 'Код возврата должен быть одним из: 301, 410'],
            self::getErrors(static fn() => new CreateRedirectAction()->run(new CreateRedirectRequest('  ', 302, '/x')))
        );
        self::assertSame(
            ['destination' => 'Укажите адрес назначения'],
            self::getErrors(static fn() => new CreateRedirectAction()->run(new CreateRedirectRequest('/a', 301, ' ')))
        );
        self::assertSame([], Redirect::find());
    }

    public function testRedirectPathIsUnique(): void {
        $first = new CreateRedirectAction()->run(new CreateRedirectRequest('/Old-Path', 410))->redirect;

        self::assertSame(
            ['path' => "Такой путь уже занят редиректом #{$first->getId()}"],
            self::getErrors(static fn() => new CreateRedirectAction()->run(new CreateRedirectRequest(' /old-path ', 410)))
        );
        self::assertCount(1, Redirect::find());
    }

    public function testSaveRedirect(): void {
        $redirect = new CreateRedirectAction()->run(new CreateRedirectRequest('/old', 410))->redirect;
        $other = new CreateRedirectAction()->run(new CreateRedirectRequest('/other', 410))->redirect;

        // The same path of the redirect itself is not a conflict.
        new SaveRedirectAction()->run(new SaveRedirectRequest($redirect->getId(), '/OLD', 301, '/new'));

        $stored = Redirect::findByUniqueIdentifier($redirect->getId());
        self::assertNotNull($stored);
        self::assertSame(['/OLD', 301, '/new'], [$stored->getPath(), $stored->getCode(), $stored->getDestination()]);

        self::assertSame(
            ['path' => "Такой путь уже занят редиректом #{$other->getId()}"],
            self::getErrors(static fn() => new SaveRedirectAction()->run(new SaveRedirectRequest($redirect->getId(), '/other', 410)))
        );
    }

    public function testSaveUnknownRedirect(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Redirect #404 not found');

        new SaveRedirectAction()->run(new SaveRedirectRequest(404, '/a', 410));
    }

    public function testValidationException(): void {
        $exception = new ActionValidationException(['a' => 'b']);

        self::assertSame('Validation failed', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame(['a' => 'b'], $exception->getErrors());
    }

    /**
     * @param callable(): mixed $action
     *
     * @return array<mixed>
     */
    public static function getErrors(callable $action): array {
        try {
            $action();
        } catch (ActionValidationException $e) {
            return $e->getErrors();
        }

        self::fail('The validation has not failed.');
    }
}
