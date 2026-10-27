<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Api\Endpoint;

use ApiPlatform\Exception\BadRequestException;
use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Fact;
use BC\Model\Media;
use BC\Model\StaticPage;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Tests\Fixture\Api\TestEndpoint;
use BC\Tests\Support\SqliteSchema;
use BC\Tests\Unit\Core\Converter\MediaConverterTest;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Exception\Exception;
use Runway\Model\AEntity;

#[CoversClass(AEndpoint::class)]
#[CoversClass(UnprocessableEntityException::class)]
#[CoversClass(NotFoundException::class)]
final class AEndpointTest extends TestCase {
    private TestEndpoint $endpoint;

    protected function setUp(): void {
        SqliteSchema::createModelTables(Fact::class, Media::class, StaticPage::class);
        $this->endpoint = new TestEndpoint();
    }

    public function testHandlerResultIsReturned(): void {
        self::assertSame(42, $this->endpoint->callHandleWithException(static fn(): int => 42));
    }

    public function testHandlerErrorIsInternalError(): void {
        $error = new LogicException('Database is gone');

        try {
            $this->endpoint->callHandleWithException(static fn() => throw $error);
            self::fail('No exception.');
        } catch (RuntimeInternalErrorException $e) {
            self::assertSame('Database is gone', $e->getMessage());
            self::assertSame(['Database is gone'], $e->errors);
            self::assertSame(500, $e->getCode());
            self::assertSame($error, $e->getPrevious());
        }
    }

    public function testActionValidationIsUnprocessableEntity(): void {
        $validation = new ActionValidationException(['title' => 'Empty']);

        try {
            $this->endpoint->callHandleActionWithException(static fn() => throw $validation, 'Cannot save');
            self::fail('No exception.');
        } catch (UnprocessableEntityException $e) {
            self::assertSame(['title' => 'Empty'], $e->getFieldErrors());
            self::assertSame('Cannot save', $e->getError());
            self::assertSame('Cannot save', $e->getMessage());
            self::assertSame(422, $e->getCode());
        }
    }

    public function testActionErrorIsInternalError(): void {
        $error = new Exception('Not found');

        try {
            $this->endpoint->callHandleActionWithException(static fn() => throw $error, 'Cannot save');
            self::fail('No exception.');
        } catch (RuntimeInternalErrorException $e) {
            self::assertSame('Not found', $e->getMessage());
            self::assertSame($error, $e->getPrevious());
        }
    }

    public function testOtherActionErrorsPassThrough(): void {
        $this->expectException(LogicException::class);

        $this->endpoint->callHandleActionWithException(static fn() => throw new LogicException('Bug'), 'Cannot save');
    }

    public function testSuccessfulAction(): void {
        $called = false;
        $this->endpoint->callHandleActionWithException(static function () use (&$called): void {
            $called = true;
        }, 'Title');

        self::assertTrue($called);
    }

    public function testUnprocessableEntityWithoutTitle(): void {
        $exception = new UnprocessableEntityException(['status' => 'Bad']);

        self::assertNull($exception->getError());
        self::assertSame('', $exception->getMessage());
        self::assertSame(['status' => 'Bad'], $exception->getFieldErrors());
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideSortDirections(): iterable {
        yield ['ASC', 'ASC'];
        yield ['DESC', 'DESC'];
        yield [' desc ', 'DESC'];
        yield ['asc', 'ASC'];
        yield ['', 'ASC'];
        yield ['DESC; DROP', 'ASC'];
        yield ['up', 'ASC'];
    }

    #[DataProvider('provideSortDirections')]
    public function testSortDirection(string $direction, string $expected): void {
        self::assertSame($expected, $this->endpoint->callSanitizeSortDirection($direction));
    }

    public function testFilter(): void {
        self::createFacts();

        $qb = Fact::getQueryBuilder()->where('id > :minId')->setVariable('minId', 1);
        self::assertSame($qb, $this->endpoint->callAddFilter($qb, '  ГОРЫ  ', ['title', 'content']));
        self::assertStringContainsString('(LOWER(title) LIKE :filter OR LOWER(content) LIKE :filter)', $qb->getSQL());

        // The filter is lowercased: SQLite lowercases only the latin letters, so the latin rows are checked here.
        $qb = Fact::getQueryBuilder()->where('id > :minId')->setVariable('minId', 1);
        $this->endpoint->callAddFilter($qb, '  ALPHA ', ['title', 'content']);
        self::assertSame(['Third alpha'], self::titles($qb->getEntities()), 'The other condition stays: OR is in parentheses.');

        $qb = Fact::getQueryBuilder();
        $this->endpoint->callAddFilter($qb, 'beta', ['content']);
        self::assertSame(['First'], self::titles($qb->getEntities()));
    }

    public function testEmptyFilterChangesNothing(): void {
        $qb = Fact::getQueryBuilder();
        $sql = $qb->getSQL();

        $this->endpoint->callAddFilter($qb, '   ', ['title']);

        self::assertSame($sql, $qb->getSQL());
    }

    public function testSortLimitAndTotal(): void {
        self::createFacts();
        $qb = Fact::getQueryBuilder()->orderBy('id', 'ASC');

        self::assertSame(3, $this->endpoint->callSetSortLimitAndGetTotal($qb, 'title', ' desc ', 1, 2, ['title']));
        self::assertSame(['Third alpha', 'Second'], self::titles($qb->getEntities()));

        $qb = Fact::getQueryBuilder()->orderBy('id', 'ASC');
        self::assertSame(3, $this->endpoint->callSetSortLimitAndGetTotal($qb, 'title', 'asc', 2, 2, ['title']));
        self::assertSame(['Third alpha'], self::titles($qb->getEntities()));
    }

    public function testDefaultOrderStaysWithoutSort(): void {
        self::createFacts();
        $qb = Fact::getQueryBuilder()->orderBy('id', 'DESC');

        self::assertSame(3, $this->endpoint->callSetSortLimitAndGetTotal($qb, '', 'ASC', 1, 25, []));
        self::assertSame(['Third alpha', 'Second', 'First'], self::titles($qb->getEntities()));
    }

    public function testPageAndPerPageAreLimited(): void {
        for ($i = 1; $i <= 105; $i++) {
            new Fact()->setTitle("Fact $i")->setContent('')->persist();
        }

        $qb = Fact::getQueryBuilder()->orderBy('id', 'ASC');
        self::assertSame(105, $this->endpoint->callSetSortLimitAndGetTotal($qb, '', '', 0, 1000, []));
        $facts = $qb->getEntities();
        self::assertCount(100, $facts);
        self::assertSame('Fact 1', self::titles($facts)[0]);

        $qb = Fact::getQueryBuilder()->orderBy('id', 'ASC');
        $this->endpoint->callSetSortLimitAndGetTotal($qb, '', '', -5, 0, []);
        self::assertSame(['Fact 1'], self::titles($qb->getEntities()));

        $qb = Fact::getQueryBuilder()->orderBy('id', 'ASC');
        $this->endpoint->callSetSortLimitAndGetTotal($qb, '', '', 3, 1, []);
        self::assertSame(['Fact 3'], self::titles($qb->getEntities()));

        $qb = Fact::getQueryBuilder()->orderBy('id', 'ASC');
        $this->endpoint->callSetSortLimitAndGetTotal($qb, '', '', 2, 100, []);
        self::assertSame(['Fact 101', 'Fact 102', 'Fact 103', 'Fact 104', 'Fact 105'], self::titles($qb->getEntities()));
    }

    public function testUnknownSortColumnIsRefused(): void {
        try {
            $this->endpoint->callSetSortLimitAndGetTotal(Fact::getQueryBuilder(), 'password', 'ASC', 1, 10, ['title', 'id']);
            self::fail('No exception.');
        } catch (BadRequestException $e) {
            self::assertSame(["Не могу сортировать по 'password'."], $e->errors);
            self::assertSame(400, $e->getCode());
        }
    }

    public function testSortColumnIsCheckedStrictly(): void {
        $this->expectException(BadRequestException::class);

        $this->endpoint->callSetSortLimitAndGetTotal(Fact::getQueryBuilder(), 'Title', 'ASC', 1, 10, ['title']);
    }

    public function testDeleteEntities(): void {
        [$first, $second, $third] = self::createFacts();

        // The IDs come from the request body: an injection in the list is just a number.
        $this->endpoint->callDeleteEntities(Fact::class, [$first->getId(), "{$third->getId()}) OR (1=1", 404]);

        self::assertSame(['Second'], self::titles(Fact::find()));
        self::assertNotNull(Fact::findByUniqueIdentifier($second->getId()));
    }

    public function testDeleteRemovesTheEntitiesOneByOne(): void {
        $media = MediaConverterTest::createMedia('missing/file.jpg', 1, 1, 'image/jpeg', '');
        $thumb = MediaConverterTest::createMedia('missing/file-1.webp', 1, 1, 'image/webp', '', $media);

        $this->endpoint->callDeleteEntities(Media::class, [$media->getId()]);

        // Media::remove() removes the thumbnails too: a bulk DELETE would leave them.
        self::assertNull(Media::findByUniqueIdentifier($thumb->getId()));
        self::assertSame([], Media::find());
    }

    public function testDeleteNothing(): void {
        self::createFacts();

        $this->endpoint->callDeleteEntities(Fact::class, [404]);

        self::assertCount(3, Fact::find());
    }

    public function testEntitiesList(): void {
        self::createFacts();

        $list = $this->endpoint->callGetEntitiesList(
            new GetEntitiesListRequest(
                qb: Fact::getQueryBuilder()->orderBy('id', 'DESC'),
                filter: 'i',
                columnsToFind: ['title'],
                sortBy: 'title',
                sortDir: 'ASC',
                page: 1,
                perPage: 1,
                sortableColumns: ['title']
            ),
            static fn(AEntity $fact): string => $fact instanceof Fact ? strtoupper($fact->getTitle()) : ''
        );

        self::assertSame(['FIRST'], $list->items);
        self::assertSame(2, $list->total);
    }

    public function testEntity(): void {
        [$fact] = self::createFacts();

        self::assertSame(
            'First',
            $this->endpoint->callGetEntity(
                Fact::class,
                $fact->getId(),
                'Not found',
                static fn(AEntity $entity): string => $entity instanceof Fact ? $entity->getTitle() : ''
            )
        );
    }

    public function testUnknownEntity(): void {
        try {
            $this->endpoint->callGetEntity(Fact::class, 404, 'Факт #{{id}} ({{id}}) не найден.', static fn(): null => null);
            self::fail('No exception.');
        } catch (NotFoundException $e) {
            self::assertSame(['Факт #404 (404) не найден.'], $e->errors);
            self::assertSame(404, $e->getCode());
        }
    }

    public function testBuildErrorIsInternalError(): void {
        [$fact] = self::createFacts();

        $this->expectException(RuntimeInternalErrorException::class);
        $this->expectExceptionMessage('Cannot build');

        $this->endpoint->callGetEntity(Fact::class, $fact->getId(), '', static fn() => throw new LogicException('Cannot build'));
    }

    public function testFindMedia(): void {
        $media = MediaConverterTest::createMedia('a.jpg', 1, 1, 'image/jpeg', '');

        self::assertNull($this->endpoint->callFindMedia(null));
        self::assertNull($this->endpoint->callFindMedia([]));
        self::assertNull($this->endpoint->callFindMedia(['id' => 404]));
        self::assertSame($media->getId(), $this->endpoint->callFindMedia(['id' => (string)$media->getId(), 'url' => 'x'])?->getId());
    }

    public function testEntityBySlug(): void {
        $about = self::createPage('about');
        $other = self::createPage('other');

        self::assertSame($about->getId(), $this->endpoint->callGetEntityBySlug(StaticPage::class, 'about')?->getId());
        self::assertNull($this->endpoint->callGetEntityBySlug(StaticPage::class, 'missing'));
        self::assertNull($this->endpoint->callGetEntityBySlug(StaticPage::class, 'about', $about->getId()), 'The entity itself is skipped.');
        self::assertSame($about->getId(), $this->endpoint->callGetEntityBySlug(StaticPage::class, 'about', $other->getId())?->getId());
        self::assertNull(
            $this->endpoint->callGetEntityBySlug(
                StaticPage::class,
                'about',
                null,
                StaticPage::getQueryBuilder()->where('published = 1')
            ),
            'The given query is used.'
        );
    }

    public function testEntityBySlugOfInvalidQuery(): void {
        self::assertNull($this->endpoint->callGetEntityBySlug(StaticPage::class, 'about', null, StaticPage::getQueryBuilder()->where('no_such_column = 1')));
    }

    /**
     * @return array{Fact, Fact, Fact}
     */
    private static function createFacts(): array {
        $facts = [];

        foreach ([['First', 'Beta text'], ['Second', 'Горы'], ['Third alpha', 'Gamma']] as [$title, $content]) {
            $fact = new Fact()->setTitle($title)->setContent($content);
            $fact->persist();
            $facts[] = $fact;
        }

        return [$facts[0], $facts[1], $facts[2]];
    }

    private static function createPage(string $slug): StaticPage {
        $page = new StaticPage()
            ->setTitle($slug)
            ->setSlug($slug)
            ->setContent([])
            ->setPublished(false)
            ->setCreatedDate(new \DateTime());
        $page->persist();

        return $page;
    }

    /**
     * @param AEntity[] $entities
     *
     * @return list<string>
     */
    private static function titles(array $entities): array {
        return array_values(array_map(static fn(AEntity $entity): string => $entity instanceof Fact ? $entity->getTitle() : '', $entities));
    }
}
